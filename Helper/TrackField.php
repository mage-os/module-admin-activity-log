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

namespace MageOS\AdminActivityLog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Model\Activity\SystemConfig;
use MageOS\AdminActivityLog\Model\Activity\ThemeConfig;

/**
 * Class Data
 * @package MageOS\AdminActivityLog\Helper
 */
class TrackField extends AbstractHelper
{
    public const SYSTEM_METHOD = 'getSystemConfigFieldData';
    public const THEME_METHOD = 'getThemeConfigFieldData';
    public const PRODUCT_METHOD = 'getProductFieldData';

    public function __construct(
        Context $context,
        protected readonly SystemConfig $systemConfig,
        protected readonly ThemeConfig $themeConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Get product module fields
     * @return string[]
     */
    public function getProductFieldData(): array
    {
        return [
            'form_key',
            'current_product_id',
            'force_reindex_eav_required',
            'news_from_date_is_formated',
            'can_save_custom_options',
            'save_rewrites_history',
            'is_custom_option_changed',
            'special_from_date_is_formated',
            'custom_design_from_is_formated',
            'affect_product_custom_options',
            'product_has_weight',
            'check_if_is_new',
            'entity_id',
            'updated_at',
            'edit_mode',
            'gift_message_available',
            'use_config_gift_message_available',
            'created_at',
            'is_changed_websites'
        ];
    }

    /**
     * Get category module fields
     * @return string[]
     */
    public function getCategoryFieldData(): array
    {
        return [
            'form_key',
            'updated_at',
            'created_at'
        ];
    }

    /**
     * Get customer module fields
     * @return string[]
     */
    public function getCustomerFieldData(): array
    {
        return [
            'id',
            'attribute_set_id',
            'entity_id',
            'form_key',
            'check_if_is_new',
            'dob_is_formated',
            'updated_at',
            'created_at',
            'rp_token',
            'rp_token_created_at',
            'is_customer_save_transaction',
            'store_id',
            'customer_id',
            'parent_id',
            'force_process'
        ];
    }

    /**
     * Get customer group modules fields
     * @return string[]
     */
    public function getCustomerGroupFieldData(): array
    {
        return [
            'customer_group_id',
            'check_if_is_new'
        ];
    }

    /**
     * Get catalog promotion modules fields
     * @return string[]
     */
    public function getCatalogPromotionFieldData(): array
    {
        return [
            'rule_id',
            'form_key',
            'check_if_is_new'
        ];
    }

    /**
     * Get cart promotion modules fields
     * @return string[]
     */
    public function getCartPromotionFieldData(): array
    {
        return [
            'is_rss',
            'form_key',
            'check_if_is_new',
            'rule_id'
        ];
    }

    /**
     * Get email modules fields
     * @return string[]
     */
    public function getEmailFieldData(): array
    {
        return [
            'template_id',
            'check_if_is_new',
            'form_key',
            'template_actual',
            'code',
            'subject',
            'sender_name',
            'sender_email',
            'text',
            'key'
        ];
    }

    /**
     * Get page modules fields
     * @return string[]
     */
    public function getPageFieldData(): array
    {
        return [
            'page_id',
            'form_key',
            'check_if_is_new',
            'store_code',
            'first_store_id'
        ];
    }

    /**
     * Get block modules fields
     * @return string[]
     */
    public function getBlockFieldData(): array
    {
        return [
            'block_id',
            'form_key',
            'check_if_is_new',
            'store_code'
        ];
    }

    /**
     * Get widget modules fields
     * @return string[]
     */
    public function getWidgetFieldData(): array
    {
        return [
            'check_if_is_new',
            'instance_id'
        ];
    }

    /**
     * Get theme configuration field data
     * @return string[]
     */
    public function getThemeConfigFieldData(): array
    {
        return [
            'back',
            'scope',
            'scope_id',
            'form_key',
            'head_includes'
        ];
    }

    /**
     * Get theme schedule field data
     * @return string[]
     */
    public function getThemeScheduleFieldData(): array
    {
        return [
            'store_id',
            'check_if_is_new'
        ];
    }

    /**
     * Get system config field data
     * @return string[]
     */
    public function getSystemConfigFieldData(): array
    {
        return [
            'check_if_is_new',
        ];
    }

    /**
     * Get attribute modules fields
     * @return string[]
     */
    public function getAttributeFieldData(): array
    {
        return [
            'form_key',
            'check_if_is_new',
            'attribute_id',
            'id',
            'modulePrefix'
        ];
    }

    /**
     * Get attribute set modules fields
     * @return string[]
     */
    public function getAttributeSetFieldData(): array
    {
        return [
            'entity_type_id',
            'check_if_is_new',
            'id'
        ];
    }

    /**
     * Get attribute set modules fields
     * @return string[]
     */
    public function getReviewRatingFieldData(): array
    {
        return [
            'rating_id',
            'check_if_is_new'
        ];
    }

    /**
     * Get review modules fields
     * @return string[]
     */
    public function getReviewFieldData(): array
    {
        return [
            'form_key',
            'entity_id',
            'check_if_is_new',
            'review_id',
            'entity_pk_value'
        ];
    }

    /**
     * Get admin user modules fields
     * @return string[]
     */
    public function getAdminUserFieldData(): array
    {
        return [
            'form_key',
            'password_confirmation',
            'current_password',
            'limit',
            'user_roles',
            'check_if_is_new',
            'user_id'
        ];
    }

    /**
     * Get admin user role modules fields
     * @return string[]
     */
    public function getAdminUserRoleFieldData(): array
    {
        return [
            'name',
            'check_if_is_new',
            'role_id'
        ];
    }

    /**
     * Get order modules fields
     * @return string[]
     */
    public function getOrderFieldData(): array
    {
        return [
            'check_if_is_new',
            'created_at',
            'updated_at',
            'entity_id',
            'id',
            'protect_code'
        ];
    }

    /**
     * Get tax rule modules fields
     * @return string[]
     */
    public function getTaxRuleFieldData(): array
    {
        return [
            'form_key',
            'check_if_is_new',
            'id',
            'tax_calculation_rule_id'
        ];
    }

    /**
     * Get tax rate modules fields
     * @return string[]
     */
    public function getTaxRateFieldData(): array
    {
        return [
            'form_key',
            'check_if_is_new',
            'tax_calculation_rate_id'
        ];
    }

    /**
     * Get url rewrites modules fields
     * @return string[]
     */
    public function getUrlRewriteFieldData(): array
    {
        return [
            'url_rewrite_id',
            'store_id'
        ];
    }

    /**
     * Get search term modules fields
     * @return string[]
     */
    public function getSearchTermFieldData(): array
    {
        return [
            'form_key',
            'check_if_is_new',
            'query_id'
        ];
    }

    /**
     * Get search synonyms modules fields
     * @return array{}
     */
    public function getSearchSynonymsFieldData(): array
    {
        return [];
    }

    /**
     * Get sitemap modules fields
     * @return string[]
     */
    public function getSitemapFieldData(): array
    {
        return [
            'form_key',
            'check_if_is_new',
            'store_id',
            'sitemap_id'
        ];
    }

    /**
     * Get checkout agreement modules fields
     * @return string[]
     */
    public function getCheckoutAgreementFieldData(): array
    {
        return [
            'form_key',
            'check_if_is_new',
            'id'
        ];
    }

    /**
     * Get Order satus modules fields
     * @return string[]
     */
    public function getOrderStatusFieldData(): array
    {
        return [
            'form_key',
            'check_if_is_new'
        ];
    }

    /**
     * Get System store modules fields
     * @return string[]
     */
    public function getSystemStoreFieldData(): array
    {
        return [
            'check_if_is_new'
        ];
    }

    /**
     * Get integration modules fields
     * @return string[]
     */
    public function getIntegrationFieldData(): array
    {
        return [
            'form_key',
            'current_password',
            'integration_id',
            'check_if_is_new',
            'consumer_id',
            'consumer_key',
            'consumer_secret',
            'identity_link_url'
        ];
    }

    /**
     * Get Edit fields which will skip
     * @return string[]
     */
    public function getSkipEditFieldData(): array
    {
        return [
            'region_code',
            'default_shipping',
            'default_billing',
            'is_default_billing',
            'is_default_shipping',
            'url_key_create_redirect',
            'attribute_set_id',
            'rp_token',
            'rp_token_created_at',
            'Page',
            'role_id',
            'field',
            'group_id',
            'scope',
            'id',
            'path',
            'config_id',
            'use_config_gift_message_available',
            'new_variations_attribute_set_id',
            'can_save_configurable_attributes',
            'type_has_options',
            'type_has_required_options',
            'special_to_date_is_formated',
            'custom_design_to_is_formated',
            'news_to_date_is_formated',
            'is_changed_categories',
            'url_key_create_redirect',
            'save_rewrites_history',
            'custom_design_from_is_formated',
            'custom_design_to_is_formated',
            'image_label',
            'small_image_label',
            'thumbnail_label'
        ];
    }

    /**
     * Get all fields by method
     * @return string[]
     */
    public function getFields(string $method): array
    {
        $fieldArray = [];
        if (!empty($method) && method_exists($this, $method)) {
            $fieldArray = $this->{$method}();
        }
        return $fieldArray;
    }

    /**
     * Get added activity data
     * @return array{}|array<string, array{
     *     old_value: string,
     *     new_value: string
     * }>
     */
    public function getAddData(DataObject $model, string $method): array
    {
        $skipFieldArray = $this->getFields($method);

        $logData = [];
        if (!empty($model->getData()) && is_array($model->getData())) {
            $logData = $this->getWildCardData($model, $method);
            foreach ($model->getData() as $key => $value) {
                if ($this->validateValue($model, $key, $value, $skipFieldArray) || empty($value)) {
                    continue;
                }
                $logData[$key] = [
                    'old_value' => '',
                    'new_value' => $value
                ];
            }
        }
        return $logData;
    }

    /**
     * Get edited activity data
     * @return array{}|array<string, array{
     *     old_value: string,
     *     new_value: string
     * }>
     */
    public function getEditData(DataObject $model, string $method): array
    {
        $fieldArray = $this->getFields($method);
        $skipFieldArray = $this->getSkipEditFieldData();

        if (Data::isWildCardModel($model)) {
            if ($method === self::SYSTEM_METHOD) {
                return $this->systemConfig->getEditData($model, $fieldArray);
            } elseif ($method === self::THEME_METHOD) {
                return $this->themeConfig->getEditData($model, $fieldArray);
            }
        }

        $logData = [];
        if (!empty($model->getData()) && is_array($model->getData())) {
            $logData = $this->getWildCardData($model, $method);
            $skipFieldArray = array_merge($skipFieldArray, $fieldArray);
            foreach ($model->getData() as $key => $value) {
                if ($this->validateValue($model, $key, $value, $skipFieldArray)) {
                    continue;
                }
                $newData = !empty($value) ? $value : '';
                $oldData = !empty($model->getOrigData($key)) ? $model->getOrigData($key) : '';
                if (!empty($newData) || !empty($oldData)) {
                    if ($newData != $oldData) {
                        $logData[$key] = [
                            'old_value' => $oldData,
                            'new_value' => $newData
                        ];
                    }
                }
            }
        }
        return $logData;
    }

    /**
     * Get deleted activity data
     * @return array{}|array<string, array{
     *     old_value: string,
     *     new_value: string
     * }>
     */
    public function getDeleteData(DataObject $model, string $method): array
    {
        $fieldArray = $this->getFields($method);

        $logData = [];
        if (!empty($model->getOrigData()) && is_array($model->getOrigData())) {
            $logData = $this->getWildCardData($model, $method);
            foreach ($model->getOrigData() as $key => $value) {
                if ($this->validateValue($model, $key, $value, $fieldArray) || empty($value)) {
                    continue;
                }
                $logData[$key] = [
                    'old_value' => $value,
                    'new_value' => ''
                ];
            }
        }
        return $logData;
    }

    /**
     * Get wild data
     * @return array{}|array<string, array{
     *     old_value: string,
     *     new_value: string
     * }>
     */
    public function getWildCardData(DataObject $model, string $method): array
    {
        $logData = [];
        if ($method === self::PRODUCT_METHOD) {
            $newQty = $model->getData('stock_data');
            $oldQty = $model->getOrigData('quantity_and_stock_status');
            if (isset($newQty['qty']) && isset($oldQty['qty']) && $newQty['qty'] != $oldQty['qty']) {
                $logData['qty'] = [
                    'old_value' => $oldQty['qty'],
                    'new_value' => $newQty['qty']
                ];
            }
        }

        return $logData;
    }

    /**
     * Skip this fields while tracking activity log
     */
    public function validateValue(DataObject $model, string $key, mixed $value, array $skipFields): bool
    {
        if (is_array($value) || is_object($value) || is_array($model->getOrigData($key))
            || in_array($key, $skipFields)) {
            return true;
        }
        return false;
    }
}
