<?php
/**
 * MageOS
 *
 * @category   MageOS
 * @package    MageOS_AdminActivityLog
 * @copyright  Copyright (C) 2018 Kiwi Commerce Ltd (https://kiwicommerce.co.uk/)
 * @copyright  Copyright (C) 2025 MageOS (https://mage-os.org/)
 * @license    https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace MageOS\AdminActivityLog\Model;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Model\Activity\SystemConfig;
use MageOS\AdminActivityLog\Model\Handler\PostDispatch;
use MageOS\AdminActivityLog\Model\Processor\ActivityContext;
use MageOS\AdminActivityLog\Model\Processor\RequestContext;

/**
 * Processor for admin activity logging
 */
class Processor
{
    public const PRIMARY_FIELD = 'id';
    public const SKIP_MODULE_ACTIONS = [
        'mui_index_render',
        'adminactivity_activity_index',
        'adminactivity_activity_log'
    ];
    public const SKIP_MODULE = [
        'mui'
    ];
    public const SALES_ORDER = 'sales_order';
    public const SAVE_ACTION = 'save';
    public const EDIT_ACTION = 'edit';

    protected string $actionName = '';
    protected string $lastAction = '';
    protected string $initAction = '';
    protected array $activityLogs = [];

    /** @var array<string> */
    protected array $urlParams = [
        '{{module}}',
        '{{controller}}',
        '{{action}}',
        '{{field}}',
        '{{id}}'
    ];

    /** @var array<string, mixed>|null */
    protected ?array $eventConfig = null;

    public function __construct(
        protected readonly Config $config,
        protected readonly Session $authSession,
        protected readonly Handler $handler,
        protected readonly StoreManagerInterface $storeManager,
        protected readonly ActivityConfigInterface $activityConfig,
        protected readonly PostDispatch $postDispatch,
        private readonly SystemConfig $systemConfig,
        private readonly RequestContext $requestContext,
        private readonly ActivityContext $activityContext
    ) {
    }

    /**
     * Get and set event config from full action name
     */
    public function init(string $fullActionName, string $actionName): static
    {
        $this->actionName = $actionName;

        if (!$this->initAction) {
            $this->initAction = $fullActionName;
        }
        $this->lastAction = $fullActionName;
        $this->eventConfig = $this->config->getEventByAction($fullActionName);
        if (isset($this->eventConfig['post_dispatch'])) {
            $this->callPostDispatchCallback();
        }
        return $this;
    }

    /**
     * Check Model class
     * @param $model
     */
    public function validate($model): bool
    {
        if ($this->activityConfig->isWildCardModel($model)) {
            if (!empty($this->activityLogs)) {
                return false;
            }
        }

        if ($this->eventConfig) {
            $usedModel = (array)$this->config->getEventModel($this->eventConfig['module']);
            $pathConst = $this->config->getActivityModuleConstant($this->eventConfig['module']);
            if (!empty($this->activityConfig->getConfigValue($pathConst))) {
                foreach ($usedModel as $module) {
                    if ($model instanceof $module) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Return skip fields or method name for TrackField class
     * @return string[]|string|false Array of skip fields, method name, or false if not found
     */
    public function getMethod(): array|string|false
    {
        return $this->config->getTrackFieldModel($this->eventConfig['module']);
    }

    /**
     * Get item url
     * @param $model
     */
    public function getEditUrl($model): string|array
    {
        $id = $model->getId();
        if ($this->eventConfig['module'] === self::SALES_ORDER && (!empty($model->getOrderId())
                || !empty($model->getParentId()))) {
            $id = $model->getOrderId() ?: $model->getParentId();
        }
        if ($this->eventConfig['module'] === SystemConfig::MODULE_SYSTEM_CONFIGURATION) {
            $id = $model->getData('field_config')['path'];
        }

        return str_replace(
            $this->urlParams,
            [
                $this->handler->getRequest()->getModuleName(),
                $this->handler->getRequest()->getControllerName(),
                $this->handler->getRequest()->getActionName(),
                self::PRIMARY_FIELD,
                $id
            ],
            $this->config->getActivityModuleEditUrl($this->eventConfig['module'])
        );
    }

    /**
     * Set activity data after item added
     * @param $model
     */
    public function modelAddAfter($model): static
    {
        if ($this->validate($model)) {
            $logData = $this->handler->modelAdd($model, $this->getMethod());
            if (!empty($logData)) {
                $activity = $this->initActivity($model);

                $this->addLog($activity, $logData, $model);
            }
        }
        return $this;
    }

    /**
     * Set activity data after item edited
     * @param $model
     */
    public function modelEditAfter($model): static
    {
        $label = ($this->eventConfig['action'] === self::SAVE_ACTION) ? self::EDIT_ACTION : $this->eventConfig['action'];
        if ($this->validate($model)) {
            $logData = $this->handler->modelEdit($model, $this->getMethod());
            if (!empty($logData)) {
                $activity = $this->initActivity($model);
                $activity->setActionType($label);

                $this->addLog($activity, $logData, $model);
            }
        }

        return $this;
    }

    /**
     * Set activity data after item deleted
     * @param $model
     */
    public function modelDeleteAfter($model): static
    {
        if ($this->validate($model)) {
            $logData = $this->handler->modelDelete($model, $this->getMethod());
            if (!empty($logData)) {
                $activity = $this->initActivity($model);
                $activity->setItemUrl('');

                $this->addLog($activity, $logData, $model);
            }
        }

        return $this;
    }

    /**
     * Set activity details data
     * @param $activity
     * @param $logData
     * @param $model
     */
    public function addLog($activity, $logData, $model): void
    {
        $this->activityLogs[] = [
            Activity::class => $activity,
            ActivityLog::class => $logData,
        ];
    }

    /**
     * Insert activity log data in database
     *
     * Uses transaction wrapping for data integrity and batch inserts
     * for improved performance when logging multiple field changes.
     */
    public function saveLogs(): bool
    {
        if (empty($this->activityLogs)) {
            return true;
        }

        $connection = $this->activityContext->getConnection();

        try {
            $connection->beginTransaction();

            foreach ($this->activityLogs as $model) {
                $activity = $model[Activity::class];
                $activity->save();
                $activityId = (int)$activity->getId();

                // Batch insert activity logs (field-level changes)
                if (isset($model[ActivityLog::class]) && !empty($model[ActivityLog::class])) {
                    $this->batchInsertActivityLogs($model[ActivityLog::class], $activityId);
                }
            }

            $connection->commit();
            $this->activityLogs = [];
        } catch (Exception $e) {
            $connection->rollBack();
            $this->activityContext->getLogger()->error('Failed to save admin activity logs', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Batch insert activity log records for improved performance
     *
     * Uses insertMultiple to reduce database round-trips when logging
     * multiple field changes in a single operation.
     *
     * @param ActivityLog[] $logs Array of activity log models
     * @param int $activityId Parent activity ID
     */
    private function batchInsertActivityLogs(array $logs, int $activityId): void
    {
        if (empty($logs)) {
            return;
        }

        $connection = $this->activityContext->getConnection();
        $tableName = $this->activityContext->getTableName('admin_activity_log');

        $insertData = [];
        foreach ($logs as $log) {
            $insertData[] = [
                'activity_id' => $activityId,
                'field_name' => $log->getFieldName(),
                'old_value' => $this->truncateValue($log->getOldValue()),
                'new_value' => $this->truncateValue($log->getNewValue()),
            ];
        }

        if (!empty($insertData)) {
            $connection->insertMultiple($tableName, $insertData);
        }
    }

    /**
     * Truncate large values to prevent database bloat
     *
     * TEXT columns can store up to 65,535 bytes. We truncate to prevent extremely large log entries.
     *
     * @param string|null $value Value to truncate
     * @return string|null Truncated value or null
     */
    private function truncateValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $maxLength = 65000; // ~64KB, leaving headroom for TEXT column
        if (strlen($value) > $maxLength) {
            return substr($value, 0, $maxLength) . '... [truncated]';
        }

        return $value;
    }

    /**
     * Set activity details data
     */
    public function initLog(): Activity
    {
        /** @var Activity $activity */
        $activity = $this->activityContext->createActivity();

        if ($this->authSession->isLoggedIn()) {
            $activity->setUsername($this->authSession->getUser()->getUsername());
            $activity->setName($this->authSession->getUser()->getName());
            $activity->setAdminId((int)$this->authSession->getUser()->getId());
        }

        $activity->setScope($this->getScope());
        $activity->setRemoteIp($this->requestContext->getRemoteAddress()->getRemoteAddress());
        $activity->setForwardedIp($this->sanitizeForwardedIp(
            $this->requestContext->getForwardedIp()
        ));
        $activity->setUserAgent($this->handler->getHeader()->getHttpUserAgent());
        $activity->setModule($this->activityConfig->getActivityModuleName($this->eventConfig['module'] ?? ''));
        $activity->setActionType($this->eventConfig['action'] ?? '');
        $activity->setFullaction($this->escapeString($this->lastAction, '/'));
        $activity->setStoreId(0);

        return $activity;
    }

    /**
     * Set activity scope, name and item url
     * @param $model
     * @return bool|Activity
     */
    public function initActivity($model): false|Activity
    {
        if (!$this->authSession->isLoggedIn()) {
            return false;
        }

        $activity = $this->initLog();

        $activity->setStoreId($this->getStoreId($model));

        $itemName = $model->getData(
            $this->config->getActivityModuleItemField($this->eventConfig['module'])
        ) ?? '';

        $itemPath = '';
        if ($this->eventConfig['module'] === 'system_configuration') {
            if (isset($itemName['label'])) {
                $itemName = $itemName['label'];
            }

            $itemPath = $this->systemConfig->getHumanReadablePath($model->getPath());
        }

        $activity->setItemName($itemName);
        $activity->setItemPath($itemPath);
        $activity->setItemUrl($this->getEditUrl($model));

        return $activity;
    }

    /**
     * Check post dispatch method to track log for mass actions
     */
    public function callPostDispatchCallback(): bool
    {
        $handler = $this->postDispatch;
        if (isset($this->eventConfig['post_dispatch'])) {
            $callback = $this->eventConfig['post_dispatch'];
            if ($handler && $callback && method_exists($handler, $callback)) {
                $handler->{$callback}($this->eventConfig, $this);
                return true;
            }
        }

        return false;
    }

    /**
     * Get store identifier
     * @param $model
     * @return int
     */
    public function getStoreId($model)
    {
        $data = $model->getData();
        if (isset($data['scope_id'])) {
            return (int)$model->getScopeId();
        }
        if (isset($data['store_id'])) {
            $storeId = $model->getStoreId();
            if (is_array($storeId)) {
                $storeId = reset($storeId);
            }

            return (int)$storeId;
        }

        return (int)$this->storeManager->getStore()->getId();
    }

    /**
     * Get scope name
     */
    public function getScope(): string
    {
        $request = $this->requestContext->getRequest();
        if ((int)$request->getParam('store') === 1 || $request->getParam('scope') === 'stores') {
            $scope = 'stores';
        } elseif ((int)$request->getParam('website') === 1) {
            $scope = 'website';
        } else {
            $scope = 'default';
        }
        return $scope;
    }

    /**
     * Convert module and action name to user readable format
     */
    public function escapeString(string $name, string $delimiter = ' '): string
    {
        return implode(
            $delimiter,
            array_map(
                'ucfirst',
                array_filter(
                    explode(
                        '_',
                        strtolower($name)
                    )
                )
            )
        );
    }

    /**
     * Check action to skip
     */
    public function isValidAction(string $module, string $fullAction): bool
    {
        if (in_array(strtolower($fullAction), self::SKIP_MODULE_ACTIONS)
            || in_array(strtolower($module), self::SKIP_MODULE)) {
            return false;
        }
        return true;
    }

    /**
     * Track page visit history
     */
    public function addPageVisitLog(string $module): void
    {
        if (in_array(strtolower($this->lastAction), $this->config->getControllerActions())) {
            return;
        }

        if ($this->activityConfig->isPageVisitEnabled()
            && $this->isValidAction($module, $this->lastAction)) {
            $activity = $this->initLog();

            $activity->setActionType('view');

            if (!$activity->getModule()) {
                $activity->setModule($this->escapeString($module));
            }

            $activity->save();
        }
    }

    public function getInitAction(): string
    {
        return $this->initAction;
    }

    public function getEventConfig(?string $type = null)
    {
        if ($type !== null) {
            return $this->eventConfig[$type] ?? null;
        }

        return $this->eventConfig;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getHandler(): Handler
    {
        return $this->handler;
    }

    public function addActivityLog(array $activityLog): void
    {
        $this->activityLogs[] = $activityLog;
    }

    public function getRequest(): RequestInterface
    {
        return $this->requestContext->getRequest();
    }

    public function getRemoteAddress(): RemoteAddress
    {
        return $this->requestContext->getRemoteAddress();
    }

    public function getRequestContext(): RequestContext
    {
        return $this->requestContext;
    }

    public function getActivityContext(): ActivityContext
    {
        return $this->activityContext;
    }

    /**
     * Sanitize and validate X-Forwarded-For header value
     *
     * The X-Forwarded-For header can be spoofed and may contain arbitrary values.
     * This method validates each IP in the header and returns only valid IPs.
     *
     * @param string|null $forwardedIp Raw X-Forwarded-For header value
     * @return string|null Sanitized IP(s) or null if no valid IPs found
     */
    public function sanitizeForwardedIp(?string $forwardedIp): ?string
    {
        if ($forwardedIp === null || $forwardedIp === '') {
            return null;
        }

        // X-Forwarded-For can contain multiple IPs: "client, proxy1, proxy2"
        $ips = array_map('trim', explode(',', $forwardedIp));
        $validIps = [];

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $validIps[] = $ip;
            }
        }

        return !empty($validIps) ? implode(', ', $validIps) : null;
    }
}
