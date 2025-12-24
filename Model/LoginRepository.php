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

use Magento\User\Model\User;
use MageOS\AdminActivityLog\Api\LoginRepositoryInterface;
use MageOS\AdminActivityLog\Model\ResourceModel\Login\Collection;
use MageOS\AdminActivityLog\Model\ResourceModel\Login\CollectionFactory;

/**
 * Class LoginRepository
 * @package MageOS\AdminActivityLog\Model
 */
class LoginRepository implements LoginRepositoryInterface
{
    public const LOGIN_SUCCESS = 1;
    public const LOGIN_FAILED = 0;

    /**
     * @var User
     */
    private $user;

    /**
     * LoginRepository constructor.
     * @param LoginFactory $loginFactory
     * @param CollectionFactory $collectionFactory
     * @param Processor $processor
     */
    public function __construct(
        protected readonly LoginFactory $loginFactory,
        protected readonly CollectionFactory $collectionFactory,
        protected readonly Processor $processor
    ) {
    }

    /**
     * Get login user
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set login user
     * @param User $user
     * @return $this
     */
    public function setUser($user): LoginRepositoryInterface
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set login activity data
     * @return Login
     */
    protected function initLoginActivity(): Login
    {
        /** @var Login $login */
        $login = $this->loginFactory->create();

        $user = $this->getUser();
        if ($user instanceof User) {
            $login->setUsername($user->getUsername());
            $login->setName(ucwords($user->getName()));
        }

        $login->setRemoteIp($this->processor->getRemoteAddress()->getRemoteAddress());
        $login->setForwardedIp((string)$this->processor->getRequest()->getServer('HTTP_X_FORWARDED_FOR'));
        $login->setUserAgent($this->processor->getHandler()->getHeader()->getHttpUserAgent());

        return $login;
    }

    /**
     * Set login data
     * @param $status
     * @param $type
     * @param string $remark
     * @return bool
     */
    public function addLog($status, $type, string $remark = ''): bool
    {
        $login = $this->initLoginActivity();

        $login->setStatus($status);
        $login->setType($type);
        $login->setRemarks($remark);
        $login->save();

        return true;
    }

    /**
     * Track login success log
     * @return void
     */
    public function addSuccessLog(): void
    {
        $this->addLog(self::LOGIN_SUCCESS, 'Login');
    }

    /**
     * Track login fail log
     * @param string $remark
     * @return void
     */
    public function addFailedLog(string $remark = ''): void
    {
        $this->addLog(self::LOGIN_FAILED, 'Login', $remark);
    }

    /**
     * Track logout success log
     * @return void
     */
    public function addLogoutLog(): void
    {
        $this->addLog(self::LOGIN_SUCCESS, 'Logout');
    }

    /**
     * Get all admin activity data before date
     * @param $endDate
     * @return Collection
     */
    public function getListBeforeDate($endDate): Collection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToSelect('entity_id')
            ->addFieldToFilter('created_at', ["lteq" => $endDate]);

        return $collection;
    }
}
