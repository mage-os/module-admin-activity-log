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
 * Activity entity data interface (admin_activity table)
 *
 * @api
 */
interface ActivityInterface
{
    public const USERNAME = 'username';
    public const NAME = 'name';
    public const ADMIN_ID = 'admin_id';
    public const STORE_ID = 'store_id';
    public const SCOPE = 'scope';
    public const ACTION_TYPE = 'action_type';
    public const REMOTE_IP = 'remote_ip';
    public const FORWARDED_IP = 'forwarded_ip';
    public const USER_AGENT = 'user_agent';
    public const MODULE = 'module';
    public const FULLACTION = 'fullaction';
    public const ITEM_NAME = 'item_name';
    public const ITEM_PATH = 'item_path';
    public const ITEM_URL = 'item_url';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getUsername(): string;

    public function setUsername(string $username): self;

    public function getName(): string;

    public function setName(string $name): self;

    public function getAdminId(): int;

    public function setAdminId(int $adminId): self;

    public function getStoreId(): int;

    public function setStoreId(int $storeId): self;

    public function getScope(): ?string;

    public function setScope(?string $scope): self;

    public function getActionType(): string;

    public function setActionType(string $actionType): self;

    public function getRemoteIp(): string;

    public function setRemoteIp(string $remoteIp): self;

    public function getForwardedIp(): ?string;

    public function setForwardedIp(?string $forwardedIp): self;

    public function getUserAgent(): string;

    public function setUserAgent(string $userAgent): self;

    public function getModule(): string;

    public function setModule(string $module): self;

    public function getFullaction(): string;

    public function setFullaction(string $fullaction): self;

    public function getItemName(): string;

    public function setItemName(string $itemName): self;

    public function getItemPath(): ?string;

    public function setItemPath(?string $itemPath): self;

    public function getItemUrl(): ?string;

    public function setItemUrl(?string $itemUrl): self;

    public function getCreatedAt(): string;

    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): string;

    public function setUpdatedAt(string $updatedAt): self;
}
