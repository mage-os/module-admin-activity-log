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

namespace MageOS\AdminActivityLog\Plugin;

use Magento\Backend\Model\Auth;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Api\LoginRepositoryInterface;

/**
 * Class Auth
 * @package MageOS\AdminActivityLog\Plugin
 */
class AuthPlugin
{
    public function __construct(
        private readonly ActivityConfigInterface $activityConfig,
        private readonly LoginRepositoryInterface $loginRepository
    ) {
    }

    /**
     * Track admin logout activity before session is destroyed
     */
    public function beforeLogout(Auth $auth): void
    {
        if ($this->activityConfig->isLoginEnabled()) {
            $user = $auth->getAuthStorage()->getUser();
            $this->loginRepository->setUser($user)->addLogoutLog();
        }
    }
}
