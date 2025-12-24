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

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class StatusColumn
 * @package MageOS\AdminActivityLog\Ui\Component\Listing\Column
 */
class RevertStatusColumn extends Column
{
    /**
     * Prepare Data Source
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $name = (string)$this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if ((bool)$item['is_revertable'] === true) {
                    $item[$name] = '<span class="grid-severity-minor" title=""><span>Revert</span></span>';
                } elseif ((bool)$item['is_revertable'] === false && !empty($item['revert_by'])) {
                    $item[$name] = '<span class="grid-severity-notice" title=""><span>Success</span></span>';
                    $item[$name] .= '<br/><strong>Reverted By:</strong> ' . $item['revert_by'];
                } else {
                    $item[$name] = '-';
                }
            }
        }

        return $dataSource;
    }
}
