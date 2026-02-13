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

use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Api\ActivityRepositoryInterface;
use MageOS\AdminActivityLog\Api\Data\ActivityInterface;
use MageOS\AdminActivityLog\Api\Data\ActivityLogDetailInterface;
use MageOS\AdminActivityLog\Api\FieldCheckerInterface;
use MageOS\AdminActivityLog\Api\ModelResolverInterface;
use MageOS\AdminActivityLog\Model\Activity\SystemConfig;
use MageOS\AdminActivityLog\Model\Activity\ThemeConfig;
use MageOS\AdminActivityLog\Model\ResourceModel\Activity\Collection as ActivityCollection;
use MageOS\AdminActivityLog\Model\ResourceModel\ActivityLog\Collection as ActivityLogCollection;
use MageOS\AdminActivityLog\Model\ResourceModel\ActivityLog\CollectionFactory;

/**
 * Repository for admin activity management
 */
class ActivityRepository implements ActivityRepositoryInterface
{
    public const THEME_MODULE = 'Themes';
    public const QTY_FIELD = 'qty';

    public function __construct(
        protected readonly ActivityFactory $activityFactory,
        protected readonly ResourceModel\Activity\CollectionFactory $collectionFactory,
        protected readonly ActivityLogDetailFactory $activityLogDetailFactory,
        protected readonly ActivityLogFactory $activityLogFactory,
        protected readonly CollectionFactory $logCollectionFactory,
        protected readonly SystemConfig $systemConfig,
        protected readonly ThemeConfig $themeConfig,
        protected readonly ModelResolverInterface $modelResolver,
        protected readonly FieldCheckerInterface $protectedFieldChecker,
        protected readonly ActivityConfigInterface $activityConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getList(): ActivityCollection
    {
        return $this->collectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getListBeforeDate(string $endDate): ActivityCollection
    {
        return $this->collectionFactory->create()
            ->addFieldToSelect('entity_id')
            ->addFieldToFilter('created_at', ["lteq" => $endDate]);
    }

    /**
     * @inheritDoc
     */
    public function deleteActivityById(int $activityId): void
    {
        $model = $this->activityFactory->create();
        $model->load($activityId);
        $model->delete();
    }

    /**
     * @inheritDoc
     */
    public function getActivityDetail(int $activityId): ActivityLogDetailInterface
    {
        return $this->activityLogDetailFactory->create()
            ->load($activityId, 'activity_id');
    }

    /**
     * @inheritDoc
     */
    public function getActivityLog(int $activityId): ActivityLogCollection
    {
        return $this->logCollectionFactory->create()
            ->addFieldToFilter('activity_id', ["eq" => $activityId]);
    }

    /**
     * Get method name
     * @param string $field
     * @return string
     */
    public function getMethodName(string $field): string
    {
        return implode(
            '',
            array_map(
                "ucfirst",
                array_map(
                    "strtolower",
                    explode('_', $field)
                )
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function revertActivity(ActivityInterface $activity): bool
    {
        $logData = $this->getActivityLog($activity->getId());
        $detailModel = $this->getActivityDetail($activity->getId());

        if ($this->activityConfig->isWildCardModel($detailModel->getModelClass())) {
            if ($activity->getModule() === self::THEME_MODULE) {
                return $this->themeConfig->revertData($logData, $activity->getStoreId(), $activity->getScope());
            }
            return $this->systemConfig->revertData($logData, $activity->getStoreId());
        }

        $model = $this->modelResolver->loadModel(
            $detailModel->getModelClass(),
            $detailModel->getItemId()
        );
        $model->setStoreId($activity->getStoreId());
        $model->setScope($activity->getScope());

        if ($model->getId()) {
            foreach ($logData as $log) {
                $fieldName = $log->getFieldName();
                if ($this->protectedFieldChecker->isFieldProtected($fieldName)) {
                    continue;
                }
                if (!preg_match('/^[a-z][a-z0-9_]{0,254}$/i', $fieldName)) {
                    continue;
                }
                if ($fieldName === self::QTY_FIELD) {
                    $model->setStockData(['qty' => $log->getOldValue()]);
                }
                $method = 'set' . $this->getMethodName($fieldName);
                $model->{$method}($log->getOldValue());
            }

            $model->setStoreId($activity->getStoreId());
            $model->save();
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getOldData(DataObject $model): DataObject|false
    {
        if ($this->activityConfig->isWildCardModel($model)) {
            return $this->systemConfig->getOldData($model);
        }

        $className = $model::class;
        if (!$this->modelResolver->isValidModelClass($className)) {
            return false;
        }

        $data = $this->modelResolver->loadModel($className, $model->getId());
        if ($data->getId()) {
            return $data;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getActivityById(int $activityId): ActivityInterface
    {
        return $this->activityFactory->create()->load($activityId);
    }
}
