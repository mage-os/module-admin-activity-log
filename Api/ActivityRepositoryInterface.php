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

namespace MageOS\AdminActivityLog\Api;

use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Api\Data\ActivityInterface;
use MageOS\AdminActivityLog\Model\ResourceModel\Activity\Collection;
use MageOS\AdminActivityLog\Model\ResourceModel\ActivityLog\Collection as ActivityLogCollection;

/**
 * Repository interface for admin activity management
 *
 * @api
 */
interface ActivityRepositoryInterface
{
    /**
     * Get collection of all admin activity records
     *
     * @return Collection
     */
    public function getList(): Collection;

    /**
     * Get all admin activity data before specified date
     *
     * @param string $endDate Date in Y-m-d H:i:s format
     * @return Collection
     */
    public function getListBeforeDate(string $endDate): Collection;

    /**
     * Remove activity log entry by ID
     *
     * @param int $activityId
     * @return void
     */
    public function deleteActivityById(int $activityId): void;

    /**
     * Get activity log collection by activity ID
     *
     * @param int $activityId
     * @return ActivityLogCollection
     */
    public function getActivityLog(int $activityId): ActivityLogCollection;

    /**
     * Get old data for a model before changes were made
     *
     * @param DataObject $model
     * @return DataObject|false
     */
    public function getOldData(DataObject $model): DataObject|false;

    /**
     * Get admin activity by ID
     *
     * @param int $activityId
     * @return ActivityInterface
     */
    public function getActivityById(int $activityId): ActivityInterface;
}
