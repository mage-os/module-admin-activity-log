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

use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use MageOS\AdminActivityLog\Api\ActivityRepositoryInterface;
use MageOS\AdminActivityLog\Helper\Data;
use MageOS\AdminActivityLog\Model\Activity\SystemConfig;
use MageOS\AdminActivityLog\Model\Activity\ThemeConfig;
use MageOS\AdminActivityLog\Model\ResourceModel\ActivityLog\Collection;
use MageOS\AdminActivityLog\Model\ResourceModel\ActivityLog\CollectionFactory;

/**
 * Class ActivityRepository
 * @package MageOS\AdminActivityLog\Model
 */
class ActivityRepository implements ActivityRepositoryInterface
{
    public const THEME_MODULE = 'Themes';
    public const QTY_FIELD = 'qty';

    /**
     * ActivityRepository constructor.
     * @param ActivityFactory $activityFactory
     * @param ResourceModel\Activity\CollectionFactory $collectionFactory
     * @param ActivityLogDetailFactory $activityLogDetailFactory
     * @param ActivityLogFactory $activityLogFactory
     * @param CollectionFactory $LogCollectionFactory
     * @param SystemConfig $systemConfig
     * @param Activity\ThemeConfig $themeConfig
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        protected readonly ActivityFactory $activityFactory,
        protected readonly ResourceModel\Activity\CollectionFactory $collectionFactory,
        protected readonly ActivityLogDetailFactory $activityLogDetailFactory,
        protected readonly ActivityLogFactory $activityLogFactory,
        protected readonly CollectionFactory $LogCollectionFactory,
        protected readonly SystemConfig $systemConfig,
        protected readonly ThemeConfig $themeConfig,
        protected readonly ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Array of protected fields
     * @return array
     */
    public function protectedFields(): array
    {
        return [
            'password'
        ];
    }

    /**
     * Get all admin activity data
     * @return ResourceModel\Activity\Collection
     */
    public function getList()
    {
        $collection = $this->collectionFactory->create();
        return $collection;
    }

    /**
     * Get all admin activity data before date
     * @param $endDate
     * @return ResourceModel\Activity\Collection
     */
    public function getListBeforeDate($endDate)
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToSelect('entity_id')
            ->addFieldToFilter('created_at', ["lteq" => $endDate]);
        return $collection;
    }

    /**
     * Remove activity log entry
     * @param int $activityId
     * @return void
     */
    public function deleteActivityById($activityId): void
    {
        $model = $this->activityFactory->create();
        $model->load((int)$activityId);
        $model->delete();
    }

    /**
     * Get all admin activity detail by activity id
     * @param int $activityId
     * @return ActivityLogDetail
     */
    public function getActivityDetail($activityId): ActivityLogDetail
    {
        $data = $this->activityLogDetailFactory->create()
            ->load($activityId, 'activity_id');
        return $data;
    }

    /**
     * Get all admin activity log by activity id
     * @param int $activityId
     * @return Collection
     */
    public function getActivityLog($activityId): Collection
    {
        $collection = $this->LogCollectionFactory->create()
            ->addFieldToFilter('activity_id', ["eq" => (int)$activityId]);
        return $collection;
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
     * Revert last changes made in module
     * @param Activity $activity
     * @return bool
     */
    public function revertActivity(Activity $activity): bool
    {
        $logData = $this->getActivityLog($activity->getId());
        $detailModel = $this->getActivityDetail($activity->getId());

        if (Data::isWildCardModel($detailModel->getModelClass())) {
            if ($activity->getModule() === self::THEME_MODULE) {
                return $this->themeConfig->revertData($logData, $activity->getStoreId(), $activity->getScope());
            }
            return $this->systemConfig->revertData($logData, $activity->getStoreId());
        }

        $model = $this->objectManager->get($detailModel->getModelClass())
            ->setStoreId($activity->getStoreId())
            ->setScope($activity->getScope())
            ->load($detailModel->getItemId());

        if ($model->getId()) {
            foreach ($logData as $log) {
                if ($this->isFieldProtected($log->getFieldName())) {
                    continue;
                }
                if ($log->getFieldName() === self::QTY_FIELD) {
                    $model->setStockData(['qty' => $log->getOldValue()]);
                }
                $method = 'set' . $this->getMethodName($log->getFieldName());
                $model->{$method}($log->getOldValue());
            }

            $model->setStoreId($activity->getStoreId());
            $model->save();
            return true;
        }
        return false;
    }

    /**
     * Get old data for system config module
     * @param DataObject $model
     * @return mixed
     */
    public function getOldData(DataObject $model)
    {
        if (Data::isWildCardModel($model)) {
            return $this->systemConfig->getOldData($model);
        }
        $data = $this->objectManager->get($model::class)->load($model->getId());
        if ($data) {
            return $data;
        }
        return false;
    }

    /**
     * Get admin activity by id
     * @param int $activityId
     * @return Activity
     */
    public function getActivityById($activityId): Activity
    {
        return $this->activityFactory->create()->load($activityId);
    }

    /**
     * Check field is protected or not
     * @param string $fieldName
     * @return bool
     */
    public function isFieldProtected(string $fieldName): bool
    {
        $fieldArray = $this->protectedFields();
        return in_array($fieldName, $fieldArray, true);
    }
}
