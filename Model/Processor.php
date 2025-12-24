<?php
/**
 * KiwiCommerce
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please contact us https://kiwicommerce.co.uk/contacts.
 *
 * @category   KiwiCommerce
 * @package    MageOS_AdminActivityLog
 * @copyright  Copyright (C) 2018 Kiwi Commerce Ltd (https://kiwicommerce.co.uk/)
 * @license    https://kiwicommerce.co.uk/magento2-extension-license/
 */

namespace MageOS\AdminActivityLog\Model;

use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\AdminActivityLog\Api\ActivityRepositoryInterface;
use MageOS\AdminActivityLog\Helper\Data as Helper;
use MageOS\AdminActivityLog\Model\Activity\Status;
use MageOS\AdminActivityLog\Model\Activity\SystemConfig;
use MageOS\AdminActivityLog\Model\Handler\PostDispatch;

/**
 * Class Processor
 * @package MageOS\AdminActivityLog\Model
 */
class Processor
{
    public const PRIMARY_FIELD = 'id';
    public const SKIP_MODULE_ACTIONS = [
        'mui_index_render',
        'adminactivity_activity_index',
        'adminactivity_activity_log',
        'adminactivity_activity_revert'
    ];
    public const SKIP_MODULE = [
        'mui'
    ];
    public const SALES_ORDER = 'sales_order';
    public const SAVE_ACTION = 'save';
    public const EDIT_ACTION = 'edit';

    /**
     * Last action name
     * @var string
     */
    protected $actionName = '';

    /**
     * Last full action name
     * @var string
     */
    protected $lastAction = '';

    /**
     * Initialization full action name
     * @var string
     */
    protected $initAction = '';

    /**
     * Temporary storage for model changes before saving to table.
     * @var array
     */
    protected $activityLogs = [];

    /**
     * @var array
     */
    protected $urlParams = [
        '{{module}}',
        '{{controller}}',
        '{{action}}',
        '{{field}}',
        '{{id}}'
    ];

    protected $eventConfig;

    /**
     * Processor constructor.
     * @param Config $config
     * @param Session $authSession
     * @param Handler $handler
     * @param RemoteAddress $remoteAddress
     * @param ActivityFactory $activityFactory
     * @param ActivityLogDetailFactory $activityDetailFactory
     * @param StoreManagerInterface $storeManager
     * @param DateTime $dateTime
     * @param ActivityRepositoryInterface $activityRepository
     * @param Helper $helper
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     * @param Http $httpRequest
     * @param Status $status
     * @param PostDispatch $postDispatch
     */
    public function __construct(
        protected readonly Config $config,
        protected readonly Session $authSession,
        protected readonly Handler $handler,
        protected readonly RemoteAddress $remoteAddress,
        protected readonly ActivityFactory $activityFactory,
        protected readonly ActivityLogDetailFactory $activityDetailFactory,
        protected readonly StoreManagerInterface $storeManager,
        protected readonly DateTime $dateTime,
        protected readonly ActivityRepositoryInterface $activityRepository,
        protected readonly Helper $helper,
        protected readonly ManagerInterface $messageManager,
        protected readonly RequestInterface $request,
        protected readonly Http $httpRequest,
        protected readonly Status $status,
        protected readonly PostDispatch $postDispatch,
        private readonly SystemConfig $systemConfig
    ) {
    }

    /**
     * Get and set event config from full action name
     * @param $fullActionName
     * @param $actionName
     * @return $this
     */
    public function init($fullActionName, $actionName)
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
     * @return bool
     */
    public function validate($model)
    {
        if (Helper::isWildCardModel($model)) {
            if (!empty($this->activityLogs)) {
                return false;
            }
        }

        if ($this->eventConfig) {
            $usedModel = (array)$this->config->getEventModel($this->eventConfig['module']);
            $pathConst = $this->config->getActivityModuleConstant($this->eventConfig['module']);
            if (!empty($this->helper->getConfigValue($pathConst))) {
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
     * Return method name of TrackField class
     * @return string
     */
    public function getMethod()
    {
        return $this->config->getTrackFieldModel($this->eventConfig['module']);
    }

    /**
     * Get item url
     * @param $model
     * @return string
     */
    public function getEditUrl($model)
    {
        $id = $model->getId();
        if ($this->eventConfig['module'] === self::SALES_ORDER && (!empty($model->getOrderId())
                || !empty($model->getParentId()))) {
            $id = ($model->getOrderId()) ? $model->getOrderId() : $model->getParentId();
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
     * @return $this|bool
     */
    public function modelAddAfter($model)
    {
        if ($this->validate($model)) {
            $logData = $this->handler->modelAdd($model, $this->getMethod());
            if (!empty($logData)) {
                $activity = $this->initActivity($model);
                $activity->setIsRevertable(false);

                $this->addLog($activity, $logData, $model);
            }
        }
        return $this;
    }

    /**
     * Set activity data after item edited
     * @param $model
     * @return $this|bool
     */
    public function modelEditAfter($model)
    {
        $label = ($this->eventConfig['action'] === self::SAVE_ACTION) ? self::EDIT_ACTION : $this->eventConfig['action'];
        if ($this->validate($model)) {
            $logData = $this->handler->modelEdit($model, $this->getMethod());
            if (!empty($logData)) {
                $activity = $this->initActivity($model);
                $activity->setActionType($label);
                $activity->setIsRevertable(true);

                $this->addLog($activity, $logData, $model);
            }
        }

        return $this;
    }

    /**
     * Set activity data after item deleted
     * @param $model
     * @return $this|bool
     */
    public function modelDeleteAfter($model)
    {
        if ($this->validate($model)) {
            $logData = $this->handler->modelDelete($model, $this->getMethod());
            if (!empty($logData)) {
                $activity = $this->initActivity($model);

                $activity->setIsRevertable(false);
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
     * @return void
     */
    public function addLog($activity, $logData, $model)
    {
        $logDetail = $this->initActivityDetail($model);
        $this->activityLogs[] = [
            Activity::class => $activity,
            ActivityLog::class => $logData,
            ActivityLogDetail::class => $logDetail
        ];
    }

    /**
     * Insert activity log data in database
     * @return bool
     */
    public function saveLogs()
    {
        try {
            if (!empty($this->activityLogs)) {
                foreach ($this->activityLogs as $model) {
                    $activity = $model[Activity::class];
                    $activity->save();
                    $activityId = $activity->getId();

                    if (isset($model[ActivityLog::class])) {
                        $logData = $model[ActivityLog::class];
                        if ($logData) {
                            foreach ($logData as $log) {
                                $log->setActivityId((int)$activityId);
                                $log->save();
                            }
                        }
                    }

                    if (isset($model[ActivityLogDetail::class])) {
                        $detail = $model[ActivityLogDetail::class];
                        $detail->setActivityId($activityId);
                        $detail->save();
                    }
                }
                $this->activityLogs = [];
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Set activity details data
     * @return Activity
     */
    public function initLog()
    {
        /** @var Activity $activity */
        $activity = $this->activityFactory->create();

        if ($this->authSession->isLoggedIn()) {
            $activity->setUsername($this->authSession->getUser()->getUsername());
            $activity->setName($this->authSession->getUser()->getName());
            $activity->setAdminId($this->authSession->getUser()->getId());
        }

        $activity->setScope($this->getScope());
        $activity->setRemoteIp($this->remoteAddress->getRemoteAddress());
        $activity->setForwardedIp($this->httpRequest->getServer('HTTP_X_FORWARDED_FOR'));
        $activity->setUserAgent($this->handler->getHeader()->getHttpUserAgent());
        $activity->setModule($this->helper->getActivityModuleName($this->eventConfig['module'] ?? ''));
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
    public function initActivity($model)
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
     * Set activity details
     * @param $model
     * @return mixed
     */
    public function initActivityDetail($model)
    {
        $activity = $this->activityDetailFactory->create()
            ->setModelClass((string)get_class($model))
            ->setItemId((int)$model->getId())
            ->setStatus('success')
            ->setResponse('');
        return $activity;
    }

    /**
     * Check post dispatch method to track log for mass actions
     * @return bool
     */
    public function callPostDispatchCallback()
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
            return $model->getScopeId();
        }
        if (isset($data['store_id'])) {
            $storeId = $model->getStoreId();
            if (is_array($storeId)) {
                $storeId = reset($storeId);
            }

            return (int)$storeId;
        }
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Get scope name
     * @return string
     */
    public function getScope()
    {
        if ((int)$this->request->getParam('store') === 1 || $this->request->getParam('scope') === 'stores') {
            $scope = 'stores';
        } elseif ((int)$this->request->getParam('website') === 1) {
            $scope = 'website';
        } else {
            $scope = 'default';
        }
        return $scope;
    }

    /**
     * Revert last changes made in module
     * @param $activityId
     * @return array
     */
    public function revertActivity($activityId)
    {
        $result = [
            'error' => true,
            'message' => __('Something went wrong, please try again')
        ];

        try {
            $activityModel = $this->activityFactory->create()->load($activityId);
            if ($activityModel->isRevertable() === false && !empty($activityModel->getRevertBy())) {
                $result['message'] = __('Activity data has already been reverted');
            } else {
                if ((int)$activityModel->getId() !== 0 && $this->activityRepository->revertActivity($activityModel)) {
                    $activityModel->setRevertBy($this->authSession->getUser()->getUsername());
                    $activityModel->setUpdatedAt($this->dateTime->gmtDate());
                    $activityModel->save();

                    $result['error'] = false;
                    $this->status->markSuccess((int)$activityId);
                    $this->messageManager->addSuccessMessage(__('Activity data has been reverted successfully'));
                }
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            $this->status->markFail($activityId);
        }

        return $result;
    }

    /**
     * Convert module and action name to user readable format
     * @param string $name
     * @param string $delimiter
     * @return string
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
     * @param string $module
     * @param string $fullAction
     * @return bool
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
     * @param string $module
     * @return void
     */
    public function addPageVisitLog(string $module): void
    {
        if (in_array(strtolower($this->lastAction), $this->config->getControllerActions())) {
            return;
        }

        if ($this->helper->isPageVisitEnable()
            && $this->isValidAction($module, $this->lastAction)) {
            $activity = $this->initLog();

            $activity->setActionType('view');
            $activity->setIsRevertable(false);

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
        return $this->request;
    }

    public function getRemoteAddress(): RemoteAddress
    {
        return $this->remoteAddress;
    }
}
