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
use Psr\Log\LoggerInterface;

/**
 * Class Auth
 * @package MageOS\AdminActivityLog\Plugin
 */
class AuthPlugin
{
    public function __construct(
        private readonly ActivityConfigInterface $activityConfig,
        private readonly LoginRepositoryInterface $loginRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Track admin logout activity before session is destroyed
     *
     * Wrapped in try/catch so activity logging never blocks admin logout.
     */
    public function beforeLogout(Auth $auth): void
    {
        try {
            if ($this->activityConfig->isLoginEnabled()) {
                $user = $auth->getAuthStorage()->getUser();
                $this->loginRepository->setUser($user)->addLogoutLog();
            }
        } catch (\Throwable $e) {
            $this->logger->error('Admin activity logout logging failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
