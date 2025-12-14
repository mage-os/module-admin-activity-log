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

namespace MageOS\AdminActivityLog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageOS\AdminActivityLog\Api\ActivityRepositoryInterface;
use MageOS\AdminActivityLog\Helper\Benchmark;
use MageOS\AdminActivityLog\Helper\Data as Helper;
use MageOS\AdminActivityLog\Model\Processor;

/**
 * Class SaveBefore
 * @package MageOS\AdminActivityLog\Observer
 */
class SaveBefore implements ObserverInterface
{
    public function __construct(
        private readonly Helper $helper,
        private readonly Processor $processor,
        private readonly ActivityRepositoryInterface $activityRepository,
        private readonly Benchmark $benchmark
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->helper->isEnable()) {
            return;
        }

        $this->benchmark->start(__METHOD__);

        $object = $observer->getEvent()->getObject();
        if ((int)$object->getId() === 0) {
            $object->setCheckIfIsNew(true);
        } else {
            $object->setCheckIfIsNew(false);
            if ($this->processor->validate($object)) {
                $origData = $object->getOrigData();
                if (!empty($origData)) {
                    return;
                }
                $data = $this->activityRepository->getOldData($object);
                foreach ($data->getData() as $key => $value) {
                    $object->setOrigData($key, $value);
                }
            }
        }

        $this->benchmark->end(__METHOD__);
    }
}
