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
use MageOS\AdminActivityLog\Api\ActivityRepositoryInterface;
use MageOS\AdminActivityLog\Model\Processor;

class SaveBefore extends AbstractActivityObserver
{
    public function __construct(
        ActivityConfigInterface $activityConfig,
        private readonly Processor $processor,
        private readonly ActivityRepositoryInterface $activityRepository
    ) {
        parent::__construct($activityConfig);
    }

    protected function process(Observer $observer): void
    {
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
                if (!$data) {
                    return;
                }
                foreach ($data->getData() as $key => $value) {
                    $object->setOrigData($key, $value);
                }
            }
        }
    }
}
