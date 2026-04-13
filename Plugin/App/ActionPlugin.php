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

namespace MageOS\AdminActivityLog\Plugin\App;

use Magento\Framework\App\Action\AbstractAction;
use MageOS\AdminActivityLog\Model\Processor;
use Psr\Log\LoggerInterface;

/**
 * Class Action
 * @package MageOS\AdminActivityLog\Plugin\App
 */
class ActionPlugin
{
    public function __construct(
        private readonly Processor $processor,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get before dispatch data
     *
     * Wrapped in try/catch so activity logging never blocks admin dispatch.
     */
    public function beforeDispatch(
        AbstractAction $subject
    ): void {
        try {
            $actionName = $subject->getRequest()->getActionName();
            $fullActionName = $subject->getRequest()->getFullActionName();

            $this->processor->init($fullActionName, $actionName);
            $this->processor->addPageVisitLog($subject->getRequest()->getModuleName());
        } catch (\Throwable $e) {
            $this->logger->error('Admin activity plugin failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
