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

namespace MageOS\AdminActivityLog\Api\Activity;

use Magento\Framework\DataObject;

/**
 * Interface ModelInterface
 * @package MageOS\AdminActivityLog\Api\Activity
 */
interface ModelInterface
{
    /**
     * @param DataObject $model
     * @return mixed
     */
    public function getOldData(DataObject $model);

    /**
     * @param DataObject $model
     * @param array<string, string> $fieldArray
     * @return array{}|array<string, array{
     *       old_value: string,
     *       new_value: string
     *   }>
     */
    public function getEditData(DataObject $model, array $fieldArray);
}
