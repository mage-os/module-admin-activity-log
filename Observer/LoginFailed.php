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

namespace MageOS\AdminActivityLog\Observer;

use Magento\Framework\Event\Observer;
use Magento\User\Model\UserFactory;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Api\LoginRepositoryInterface;
use Psr\Log\LoggerInterface;

class LoginFailed extends AbstractActivityObserver
{
    public function __construct(
        ActivityConfigInterface $activityConfig,
        LoggerInterface $logger,
        private readonly UserFactory $userFactory,
        private readonly LoginRepositoryInterface $loginRepository
    ) {
        parent::__construct($activityConfig, $logger);
    }

    protected function isEnabled(): bool
    {
        return $this->activityConfig->isLoginEnabled();
    }

    protected function process(Observer $observer): void
    {
        $user = $this->userFactory->create();
        $user->setUserName($observer->getUserName());
        if ($observer->getUserName()) {
            $user = $user->loadByUsername($observer->getUserName());
        }

        $this->loginRepository->setUser($user)
            ->addFailedLog($observer->getException()->getMessage());
    }
}
