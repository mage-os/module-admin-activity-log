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

namespace MageOS\AdminActivityLog\Plugin\User;

use Magento\Framework\Model\AbstractModel;
use Magento\User\Model\ResourceModel\User;
use Psr\Log\LoggerInterface;

/**
 * Class Delete
 * @package MageOS\AdminActivityLog\Plugin\User
 */
class DeletePlugin
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Reload user data before delete so the DeleteAfter observer can log field values.
     *
     * Wrapped in try/catch so activity logging never blocks user deletion.
     */
    public function beforeDelete(User $subject, AbstractModel $user): void
    {
        try {
            $user->load($user->getId());
        } catch (\Throwable $e) {
            $this->logger->error('Admin activity pre-delete user load failed', [
                'user_id' => $user->getId(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
