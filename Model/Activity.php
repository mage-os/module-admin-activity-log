<?php
declare(strict_types=1);
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

use Magento\Framework\Model\AbstractModel;
use MageOS\AdminActivityLog\Api\Data\ActivityInterface;

class Activity extends AbstractModel implements ActivityInterface
{
    public const ACTIVITY_ID = 'entity_id';

    public function _construct(): void
    {
        $this->_init(ResourceModel\Activity::class);
    }

    public function getUsername(): string
    {
        return (string)$this->getData(self::USERNAME);
    }

    public function setUsername(string $username): ActivityInterface
    {
        return $this->setData(self::USERNAME, $username);
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): ActivityInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getAdminId(): int
    {
        return (int)$this->getData(self::ADMIN_ID);
    }

    public function setAdminId(int $adminId): ActivityInterface
    {
        return $this->setData(self::ADMIN_ID, $adminId);
    }

    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    public function setStoreId(int $storeId): ActivityInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getScope(): ?string
    {
        $value = $this->getData(self::SCOPE);
        return $value === null ? null : (string)$value;
    }

    public function setScope(?string $scope): ActivityInterface
    {
        return $this->setData(self::SCOPE, $scope);
    }

    public function getActionType(): string
    {
        return (string)$this->getData(self::ACTION_TYPE);
    }

    public function setActionType(string $actionType): ActivityInterface
    {
        return $this->setData(self::ACTION_TYPE, $actionType);
    }

    public function getRemoteIp(): string
    {
        return (string)$this->getData(self::REMOTE_IP);
    }

    public function setRemoteIp(string $remoteIp): ActivityInterface
    {
        return $this->setData(self::REMOTE_IP, $remoteIp);
    }

    public function getForwardedIp(): ?string
    {
        $value = $this->getData(self::FORWARDED_IP);
        return $value === null ? null : (string)$value;
    }

    public function setForwardedIp(?string $forwardedIp): ActivityInterface
    {
        return $this->setData(self::FORWARDED_IP, $forwardedIp);
    }

    public function getUserAgent(): string
    {
        return (string)$this->getData(self::USER_AGENT);
    }

    public function setUserAgent(string $userAgent): ActivityInterface
    {
        return $this->setData(self::USER_AGENT, $userAgent);
    }

    public function getModule(): string
    {
        return (string)$this->getData(self::MODULE);
    }

    public function setModule(string $module): ActivityInterface
    {
        return $this->setData(self::MODULE, $module);
    }

    public function getFullaction(): string
    {
        return (string)$this->getData(self::FULLACTION);
    }

    public function setFullaction(string $fullaction): ActivityInterface
    {
        return $this->setData(self::FULLACTION, $fullaction);
    }

    public function getItemName(): string
    {
        return (string)$this->getData(self::ITEM_NAME);
    }

    public function setItemName(string $itemName): ActivityInterface
    {
        return $this->setData(self::ITEM_NAME, $itemName);
    }

    public function getItemPath(): string
    {
        return (string)$this->getData(self::ITEM_PATH);
    }

    public function setItemPath(string $itemPath): ActivityInterface
    {
        return $this->setData(self::ITEM_PATH, $itemPath);
    }

    public function getItemUrl(): ?string
    {
        $value = $this->getData(self::ITEM_URL);
        return $value === null ? null : (string)$value;
    }

    public function setItemUrl(?string $itemUrl): ActivityInterface
    {
        return $this->setData(self::ITEM_URL, $itemUrl);
    }

    public function isRevertable(): ?bool
    {
        $value = $this->getData(self::IS_REVERTABLE);
        return $value === null ? null : (bool)$value;
    }

    public function setIsRevertable(?bool $isRevertable): ActivityInterface
    {
        return $this->setData(self::IS_REVERTABLE, $isRevertable);
    }

    public function getRevertBy(): ?string
    {
        $value = $this->getData(self::REVERT_BY);
        return $value === null ? null : (string)$value;
    }

    public function setRevertBy(?string $revertBy): ActivityInterface
    {
        return $this->setData(self::REVERT_BY, $revertBy);
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): ActivityInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): ActivityInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
