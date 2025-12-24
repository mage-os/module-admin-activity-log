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

namespace MageOS\AdminActivityLog\Ui\Component\Listing\Column;

use Magento\Store\Ui\Component\Listing\Column\Store;

/**
 * Class StoreColumn
 * @package MageOS\AdminActivityLog\Ui\Component\Listing\Column
 */
class StoreColumn extends Store
{
    public const KEY_FIELD = 'store_id';

    /**
     * Prepare Item
     * @param array<string, string> $item
     */
    public function prepareItem(array $item): string
    {
        $this->storeKey = !empty($this->storeKey) ? $this->storeKey : self::KEY_FIELD;
        if (isset($item[$this->storeKey]) && (int)$item[$this->storeKey] === 0) {
            $origStores['0'] = 0;
        }

        if (!empty($item[$this->storeKey])) {
            $origStores = $item[$this->storeKey];
        }

        if (empty($origStores)) {
            return '';
        }
        if (!is_array($origStores)) {
            $origStores = [$origStores];
        }
        if (in_array(0, $origStores, true) && count($origStores) === 1) {
            return __('All Store Views');
        }

        $data = $this->systemStore->getStoresStructure(false, $origStores);

        $content = '';
        foreach ($data as $website) {
            $content .= $website['label'] . "<br/>";
            foreach ($website['children'] as $group) {
                $content .= str_repeat('&nbsp;', 3) . $this->escaper->escapeHtml($group['label']) . "<br/>";
                foreach ($group['children'] as $store) {
                    $content .= str_repeat('&nbsp;', 6) . $this->escaper->escapeHtml($store['label']) . "<br/>";
                }
            }
        }

        return $content;
    }
}
