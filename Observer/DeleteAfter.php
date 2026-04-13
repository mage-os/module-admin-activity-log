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
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Model\Processor;
use Psr\Log\LoggerInterface;

class DeleteAfter extends AbstractActivityObserver
{
    public const SYSTEM_CONFIG = 'adminhtml_system_config_save';

    public function __construct(
        ActivityConfigInterface $activityConfig,
        LoggerInterface $logger,
        private readonly Processor $processor
    ) {
        parent::__construct($activityConfig, $logger);
    }

    protected function process(Observer $observer): void
    {
        $object = $observer->getEvent()->getObject();
        if ($this->processor->validate($object) && ($this->processor->getInitAction() === self::SYSTEM_CONFIG)) {
            $this->processor->modelEditAfter($object);
        }
        $this->processor->modelDeleteAfter($object);
    }
}
