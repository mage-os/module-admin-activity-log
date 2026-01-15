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

namespace MageOS\AdminActivityLog\Api;

use Magento\Framework\DataObject;

/**
 * Service interface for admin activity configuration
 *
 * @api
 */
interface ActivityConfigInterface
{
    /**
     * Check if admin activity logging is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Check if login activity tracking is enabled
     *
     * Requires both module and login activity to be enabled.
     *
     * @return bool
     */
    public function isLoginEnabled(): bool;

    /**
     * Check if page visit tracking is enabled
     *
     * Requires both module and page visit to be enabled.
     *
     * @return bool
     */
    public function isPageVisitEnabled(): bool;

    /**
     * Get configuration value by constant name
     *
     * @param string $constantName Name of the constant (e.g., 'CLEAR_LOG_DAYS')
     * @return mixed Configuration value or false if not found
     */
    public function getConfigValue(string $constantName): mixed;

    /**
     * Get translated label for an action
     *
     * @param string $action Action identifier
     * @return string Translated label
     */
    public function getActionTranslatedLabel(string $action): string;

    /**
     * Get all available actions
     *
     * @return array<string, mixed>
     */
    public function getAllActions(): array;

    /**
     * Get human-readable activity module name
     *
     * @param string $module Module identifier
     * @return string Module name
     */
    public function getActivityModuleName(string $module): string;

    /**
     * Check if model is a wildcard model (e.g., system config value)
     *
     * Wildcard models require special handling for activity tracking.
     *
     * @param DataObject|string $model Model instance or class name
     * @return bool
     */
    public function isWildCardModel(DataObject|string $model): bool;
    
    /**
     * Get number of days to keep activity logs
     *
     * @return int Number of days
     */
    public function getClearLogDays(): int;
}
