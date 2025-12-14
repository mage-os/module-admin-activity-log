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

namespace MageOS\AdminActivityLog\Ui\Component\Listing\Column\ActionType;

use Magento\Framework\Option\ArrayInterface;
use MageOS\AdminActivityLog\Helper\Data;

/**
 * Class Options
 * @package MageOS\AdminActivityLog\Ui\Component\Listing\Column\ActionType
 */
class Options implements ArrayInterface
{
    public function __construct(
        private readonly Data $helper
    ) {
    }

    /**
     * List all option to get in filter
     * @return array
     */
    public function toOptionArray(): array
    {
        $data = [];
        $labelList = $this->helper->getAllActions();
        foreach ($labelList as $key => $value) {
            $data[] = ['value' => $key, 'label' => __($value)];
        }
        return $data;
    }
}
