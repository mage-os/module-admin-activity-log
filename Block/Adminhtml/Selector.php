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

namespace MageOS\AdminActivityLog\Block\Adminhtml;

use Magento\Backend\Block\Template;

/**
 * Class Selector
 * @package MageOS\AdminActivityLog\Block\Adminhtml
 */
class Selector extends Template
{
    /**
     * Revert Activity Log action URL
     */
    public function getRevertUrl(): string
    {
        return $this->getUrl('adminactivity/activity/revert');
    }
}
