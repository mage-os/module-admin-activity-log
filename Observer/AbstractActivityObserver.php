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
use Magento\Framework\Event\ObserverInterface;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Abstract base class for admin activity observers
 *
 * Encapsulates common observer patterns:
 * - Module enable check
 * - Fail-safe error handling (logging errors never block admin actions)
 */
abstract class AbstractActivityObserver implements ObserverInterface
{
    public function __construct(
        protected readonly ActivityConfigInterface $activityConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute observer with standard enable check and fail-safe error handling
     */
    public function execute(Observer $observer): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $this->process($observer);
        } catch (\Throwable $e) {
            $this->logger->error('Admin activity observer failed', [
                'observer' => static::class,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Check if the module functionality is enabled
     *
     * Override in subclasses for different enable checks (e.g., login-specific)
     */
    protected function isEnabled(): bool
    {
        return $this->activityConfig->isEnabled();
    }

    /**
     * Process the observer event
     *
     * Implement this method in subclasses to handle the specific event logic
     */
    abstract protected function process(Observer $observer): void;
}
