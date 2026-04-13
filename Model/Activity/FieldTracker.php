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

namespace MageOS\AdminActivityLog\Model\Activity;

use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Api\FieldCheckerInterface;
use MageOS\AdminActivityLog\Api\FieldTrackerInterface;
use MageOS\AdminActivityLog\Model\Config;

/**
 * Service for tracking field changes in admin activity log
 */
class FieldTracker implements FieldTrackerInterface
{
    /**
     * Maximum length for stored field values (in bytes)
     * TEXT columns support up to 65,535 bytes; cut off before
     */
    private const MAX_VALUE_LENGTH = 65000;

    /**
     * Placeholder value for redacted sensitive fields
     */
    private const REDACTED_VALUE = '******';

    /**
     * Method name constants for wildcard model detection
     */
    private const SYSTEM_METHOD = 'getSystemConfigFieldData';
    private const THEME_METHOD = 'getThemeConfigFieldData';
    private const PRODUCT_METHOD = 'getProductFieldData';

    /**
     * @param SystemConfig $systemConfig System configuration handler
     * @param ThemeConfig $themeConfig Theme configuration handler
     * @param Config $config Activity configuration
     * @param ActivityConfigInterface $activityConfig Activity config service
     * @param FieldCheckerInterface $fieldChecker Protected field checker
     */
    public function __construct(
        private readonly SystemConfig $systemConfig,
        private readonly ThemeConfig $themeConfig,
        private readonly Config $config,
        private readonly ActivityConfigInterface $activityConfig,
        private readonly FieldCheckerInterface $fieldChecker
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getSkipEditFieldData(): array
    {
        return $this->config->getGlobalSkipEditFields();
    }

    /**
     * @inheritDoc
     */
    public function getFields(string|array $methodOrFields): array
    {
        // New XML-based config: skip_fields array passed directly
        if (is_array($methodOrFields)) {
            return $methodOrFields;
        }

        // Legacy support: method name passed as string
        if (!empty($methodOrFields) && method_exists($this, $methodOrFields)) {
            return $this->{$methodOrFields}();
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAddData(DataObject $model, string|array $methodOrFields): array
    {
        $skipFieldArray = $this->getFields($methodOrFields);

        $logData = [];
        if (!empty($model->getData()) && is_array($model->getData())) {
            $logData = $this->getWildCardData($model, $methodOrFields);
            foreach ($model->getData() as $key => $value) {
                if ($this->validateValue($model, $key, $value, $skipFieldArray) || empty($value)) {
                    continue;
                }
                $logData[$key] = $this->redactIfProtected($key, [
                    'old_value' => '',
                    'new_value' => $this->prepareValue($value)
                ]);
            }
        }
        return $logData;
    }

    /**
     * @inheritDoc
     */
    public function getEditData(DataObject $model, string|array $methodOrFields): array
    {
        $fieldArray = $this->getFields($methodOrFields);
        $skipFieldArray = $this->getSkipEditFieldData();

        if ($this->activityConfig->isWildCardModel($model)) {
            $isSystemConfig = $this->isSystemConfigMethod($methodOrFields);
            $isThemeConfig = $this->isThemeConfigMethod($methodOrFields);

            if ($isSystemConfig) {
                return $this->systemConfig->getEditData($model, $fieldArray);
            } elseif ($isThemeConfig) {
                return $this->themeConfig->getEditData($model, $fieldArray);
            }
        }

        $logData = [];
        if (!empty($model->getData()) && is_array($model->getData())) {
            $logData = $this->getWildCardData($model, $methodOrFields);
            $skipFieldArray = array_merge($skipFieldArray, $fieldArray);
            foreach ($model->getData() as $key => $value) {
                if ($this->validateValue($model, $key, $value, $skipFieldArray)) {
                    continue;
                }
                $newData = $this->normalizeValue($value);
                $oldData = $this->normalizeValue($model->getOrigData($key));
                if ($newData !== '' || $oldData !== '') {
                    if ($newData !== $oldData) {
                        $logData[$key] = $this->redactIfProtected($key, [
                            'old_value' => $this->prepareValue($oldData),
                            'new_value' => $this->prepareValue($newData)
                        ]);
                    }
                }
            }
        }
        return $logData;
    }

    /**
     * @inheritDoc
     */
    public function getDeleteData(DataObject $model, string|array $methodOrFields): array
    {
        $fieldArray = $this->getFields($methodOrFields);

        $logData = [];
        if (!empty($model->getOrigData()) && is_array($model->getOrigData())) {
            $logData = $this->getWildCardData($model, $methodOrFields);
            foreach ($model->getOrigData() as $key => $value) {
                if ($this->validateValue($model, $key, $value, $fieldArray) || empty($value)) {
                    continue;
                }
                $logData[$key] = $this->redactIfProtected($key, [
                    'old_value' => $this->prepareValue($value),
                    'new_value' => ''
                ]);
            }
        }
        return $logData;
    }

    /**
     * @inheritDoc
     */
    public function getWildCardData(DataObject $model, string|array $methodOrFields): array
    {
        $logData = [];

        $isProductMethod = $this->isProductMethod($methodOrFields);

        if ($isProductMethod) {
            $newQty = $model->getData('stock_data');
            $oldQty = $model->getOrigData('quantity_and_stock_status');
            if (isset($newQty['qty']) && isset($oldQty['qty']) && $newQty['qty'] !== $oldQty['qty']) {
                $logData['qty'] = [
                    'old_value' => $this->prepareValue($oldQty['qty']),
                    'new_value' => $this->prepareValue($newQty['qty'])
                ];
            }
        }

        return $logData;
    }

    /**
     * @inheritDoc
     */
    public function validateValue(DataObject $model, string $key, mixed $value, array $skipFields): bool
    {
        if (is_array($value) || is_object($value) || is_array($model->getOrigData($key))
            || in_array($key, $skipFields, true)) {
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function truncateValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!is_string($value)) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($value === false) {
                    return '[unserializable]';
                }
            } else {
                $value = (string)$value;
            }
        }

        if (strlen($value) > self::MAX_VALUE_LENGTH) {
            return substr($value, 0, self::MAX_VALUE_LENGTH - 20) . '... [truncated]';
        }

        return $value;
    }

    /**
     * Check if method/fields indicates system config
     */
    private function isSystemConfigMethod(string|array $methodOrFields): bool
    {
        if (is_string($methodOrFields)) {
            return $methodOrFields === self::SYSTEM_METHOD;
        }
        return $methodOrFields === ['check_if_is_new'];
    }

    /**
     * Check if method/fields indicates theme config
     */
    private function isThemeConfigMethod(string|array $methodOrFields): bool
    {
        if (is_string($methodOrFields)) {
            return $methodOrFields === self::THEME_METHOD;
        }
        return in_array('head_includes', $methodOrFields, true)
            && in_array('scope', $methodOrFields, true);
    }

    /**
     * Check if method/fields indicates product module
     */
    private function isProductMethod(string|array $methodOrFields): bool
    {
        if (is_string($methodOrFields)) {
            return $methodOrFields === self::PRODUCT_METHOD;
        }
        return in_array('current_product_id', $methodOrFields, true)
            || in_array('product_has_weight', $methodOrFields, true);
    }

    /**
     * Normalize a scalar value to a string for comparison
     *
     * Uses (string) cast instead of empty() to preserve meaningful values
     * like "0" and 0 that empty() would incorrectly treat as blank.
     *
     * @param mixed $value Raw value from model data or origData
     * @return string Normalized string for comparison
     */
    private function normalizeValue(mixed $value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }

        return (string)$value;
    }

    /**
     * Prepare a value for storage, applying truncation
     *
     * @param mixed $value Raw value from model
     * @return string Prepared value ready for storage
     */
    private function prepareValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return $this->truncateValue($value);
    }

    /**
     * Replace values with a redacted placeholder if the field is protected
     *
     * Empty strings are preserved so add/delete action semantics remain clear
     * (add has old_value='', delete has new_value='').
     *
     * @param string $fieldName Field name to check
     * @param array{old_value: string, new_value: string} $entry Log entry
     * @return array{old_value: string, new_value: string}
     */
    private function redactIfProtected(string $fieldName, array $entry): array
    {
        if ($this->fieldChecker->isFieldProtected($fieldName)) {
            if ($entry['old_value'] !== '') {
                $entry['old_value'] = self::REDACTED_VALUE;
            }
            if ($entry['new_value'] !== '') {
                $entry['new_value'] = self::REDACTED_VALUE;
            }
        }

        return $entry;
    }
}
