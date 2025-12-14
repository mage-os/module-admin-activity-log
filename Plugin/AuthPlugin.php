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

namespace MageOS\AdminActivityLog\Plugin;

use Magento\Backend\Model\Auth;
use MageOS\AdminActivityLog\Api\LoginRepositoryInterface;
use MageOS\AdminActivityLog\Helper\Benchmark;
use MageOS\AdminActivityLog\Helper\Data as Helper;

/**
 * Class Auth
 * @package MageOS\AdminActivityLog\Plugin
 */
class AuthPlugin
{
    public function __construct(
        private readonly Helper $helper,
        private readonly LoginRepositoryInterface $loginRepository,
        private readonly Benchmark $benchmark
    ) {
    }

    /**
     * Track admin logout activity
     */
    public function aroundLogout(Auth $auth, callable $proceed): void
    {
        $this->benchmark->start(__METHOD__);

        if ($this->helper->isLoginEnable()) {
            $user = $auth->getAuthStorage()->getUser();
            $this->loginRepository->setUser($user)->addLogoutLog();
        }

        $proceed();

        $this->benchmark->end(__METHOD__);
    }
}
