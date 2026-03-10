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
 * Activity Log Detail entity data interface (admin_activity_detail table)
 *
 * @api
 */
interface ActivityLogDetailInterface
{
    public const ACTIVITY_ID = 'activity_id';
    public const MODEL_CLASS = 'model_class';
    public const ITEM_ID = 'item_id';

    public function getActivityId(): int;

    public function setActivityId(int $activityId): self;

    public function getModelClass(): string;

    public function setModelClass(string $modelClass): self;

    public function getItemId(): ?int;

    public function setItemId(?int $itemId): self;
}
