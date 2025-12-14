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

namespace MageOS\AdminActivityLog\Plugin\App;

use Magento\Framework\App\Action\AbstractAction;
use MageOS\AdminActivityLog\Helper\Benchmark;
use MageOS\AdminActivityLog\Model\Processor;

/**
 * Class Action
 * @package MageOS\AdminActivityLog\Plugin\App
 */
class ActionPlugin
{
    public function __construct(
        private readonly Processor $processor,
        private readonly Benchmark $benchmark
    ) {
    }

    /**
     * Get before dispatch data
     */
    public function beforeDispatch(
        AbstractAction $subject
    ): void {
        $this->benchmark->start(__METHOD__);
        $actionName = $subject->getRequest()->getActionName();
        $fullActionName = $subject->getRequest()->getFullActionName();

        $this->processor->init($fullActionName, $actionName);
        $this->processor->addPageVisitLog($subject->getRequest()->getModuleName());
        $this->benchmark->end(__METHOD__);
    }
}
