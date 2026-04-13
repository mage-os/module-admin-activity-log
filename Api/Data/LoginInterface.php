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
 * Login activity entity data interface (admin_login_log table)
 *
 * @api
 */
interface LoginInterface
{
    public const ADMIN_ID     = 'admin_id';
    public const USERNAME     = 'username';
    public const NAME         = 'name';
    public const REMOTE_IP    = 'remote_ip';
    public const FORWARDED_IP = 'forwarded_ip';
    public const USER_AGENT   = 'user_agent';
    public const TYPE         = 'type';
    public const STATUS       = 'status';
    public const REMARKS      = 'remarks';
    public const CREATED_AT   = 'created_at';

    public function getAdminId(): ?int;
    public function setAdminId(?int $adminId): self;

    public function getUsername(): string;
    public function setUsername(string $username): self;

    public function getName(): string;
    public function setName(string $name): self;

    public function getRemoteIp(): string;
    public function setRemoteIp(string $remoteIp): self;

    public function getForwardedIp(): ?string;
    public function setForwardedIp(?string $forwardedIp): self;

    public function getUserAgent(): string;
    public function setUserAgent(string $userAgent): self;

    public function getType(): string;
    public function setType(string $type): self;

    public function getStatus(): ?bool;
    public function setStatus(?bool $status): self;

    public function getRemarks(): ?string;
    public function setRemarks(?string $remarks): self;

    public function getCreatedAt(): string;
    public function setCreatedAt(string $createdAt): self;
}
