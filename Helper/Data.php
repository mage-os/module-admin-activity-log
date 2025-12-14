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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use MageOS\AdminActivityLog\Model\Config;

/**
 * Class Data
 * @package MageOS\AdminActivityLog\Helper
 */
class Data extends AbstractHelper
{
    public const ACTIVITY_ENABLE = 'admin/admin_activity/general/enable';
    public const LOGIN_ACTIVITY_ENABLE = 'admin/admin_activity/general/login_activity';
    public const PAGE_VISIT_ENABLE = 'admin/admin_activity/general/page_visit';
    public const CLEAR_LOG_DAYS = 'admin/admin_activity/general/clearlog';
    public const MODULE_ORDER = 'admin/admin_activity/module/order';
    public const MODULE_PRODUCT = 'admin/admin_activity/module/product';
    public const MODULE_CATEGORY = 'admin/admin_activity/module/category';
    public const MODULE_CUSTOMER = 'admin/admin_activity/module/customer';
    public const MODULE_PROMOTION = 'admin/admin_activity/module/promotion';
    public const MODULE_EMAIL = 'admin/admin_activity/module/email';
    public const MODULE_PAGE = 'admin/admin_activity/module/page';
    public const MODULE_BLOCK = 'admin/admin_activity/module/block';
    public const MODULE_WIDGET = 'admin/admin_activity/module/widget';
    public const MODULE_THEME = 'admin/admin_activity/module/theme';
    public const MODULE_SYSTEM_CONFIG = 'admin/admin_activity/module/system_config';
    public const MODULE_ATTRIBUTE = 'admin/admin_activity/module/attibute';
    public const MODULE_ADMIN_USER = 'admin/admin_activity/module/admin_user';
    public const MODULE_SEO = 'admin/admin_activity/module/seo';

    /**
     * @var array
     */
    public static $wildcardModels = [
        Value::class
    ];

    /**
     * Data constructor.
     * @param Context $context
     * @param Config $config
     */
    public function __construct(
        Context $context,
        protected readonly Config $config
    ) {
        parent::__construct($context);
    }

    /**
     * Check and return status of module
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->scopeConfig->isSetFlag(self::ACTIVITY_ENABLE);
    }

    /**
     * Check and return status for login activity
     * @return bool
     */
    public function isLoginEnable(): bool
    {
        $status = $this->scopeConfig->isSetFlag(self::ACTIVITY_ENABLE);
        $loginStatus = $this->scopeConfig->isSetFlag(self::LOGIN_ACTIVITY_ENABLE);

        return $status && $loginStatus;
    }

    /**
     * Check and return status for page visit history
     * @return bool
     */
    public function isPageVisitEnable(): bool
    {
        $status = $this->scopeConfig->isSetFlag(self::ACTIVITY_ENABLE);
        $pageVisitStatus = $this->scopeConfig->isSetFlag(self::PAGE_VISIT_ENABLE);

        return $status && $pageVisitStatus;
    }

    /**
     * Get value of system config from path
     * @param $path
     * @return mixed|false
     */
    public function getConfigValue(string $path)
    {
        $moduleValue = $this->scopeConfig->getValue(
            constant(
                'self::'
                . $path
            ),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        if ($moduleValue) {
            return $moduleValue;
        }
        return false;
    }

    /**
     * Get translated label by action name
     * @param string $action
     * @return string
     */
    public function getActionTranslatedLabel(string $action): string
    {
        return (string)$this->config->getActionLabel($action);
    }

    /**
     * Get all actions
     * @return array
     */
    public function getAllActions(): array
    {
        return $this->config->getActions();
    }

    /**
     * Get activity module name
     * @return string
     */
    public function getActivityModuleName(string $module)
    {
        return $this->config->getActivityModuleName($module);
    }

    /**
     * Get module name is valid or not
     * @param $model
     * @return bool
     */
    public static function isWildCardModel($model): bool
    {
        $model = is_string($model) ? $model : $model::class;
        return in_array($model, self::$wildcardModels, true);
    }
}
