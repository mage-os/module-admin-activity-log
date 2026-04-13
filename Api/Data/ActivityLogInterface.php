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

namespace MageOS\AdminActivityLog\Api\Data;

/**
 * Activity Log entity data interface (admin_activity_log table)
 *
 * @api
 */
interface ActivityLogInterface
{
    public const ACTIVITY_ID = 'activity_id';
    public const FIELD_NAME  = 'field_name';
    public const OLD_VALUE   = 'old_value';
    public const NEW_VALUE   = 'new_value';

    public function getActivityId(): ?int;
    public function setActivityId(?int $activityId): self;

    public function getFieldName(): string;
    public function setFieldName(string $fieldName): self;

    public function getOldValue(): ?string;
    public function setOldValue(?string $oldValue): self;

    public function getNewValue(): ?string;
    public function setNewValue(?string $newValue): self;
}
