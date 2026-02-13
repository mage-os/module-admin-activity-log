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

use Magento\Framework\Model\AbstractModel;
use MageOS\AdminActivityLog\Api\Data\ActivityLogDetailInterface;

class ActivityLogDetail extends AbstractModel implements ActivityLogDetailInterface
{
    public const ACTIVITYLOGDETAIL_ID = 'entity_id';

    public function _construct(): void
    {
        $this->_init(ResourceModel\ActivityLogDetail::class);
    }

    public function getActivityId(): int
    {
        return (int)$this->getData(self::ACTIVITY_ID);
    }

    public function setActivityId(int $activityId): ActivityLogDetailInterface
    {
        return $this->setData(self::ACTIVITY_ID, $activityId);
    }

    public function getModelClass(): string
    {
        return (string)$this->getData(self::MODEL_CLASS);
    }

    public function setModelClass(string $modelClass): ActivityLogDetailInterface
    {
        return $this->setData(self::MODEL_CLASS, $modelClass);
    }

    public function getItemId(): ?int
    {
        $value = $this->getData(self::ITEM_ID);
        return $value === null ? null : (int)$value;
    }

    public function setItemId(?int $itemId): ActivityLogDetailInterface
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }
}
