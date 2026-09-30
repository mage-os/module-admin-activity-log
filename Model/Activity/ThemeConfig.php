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
use MageOS\AdminActivityLog\Api\Activity\ModelInterface;

/**
 * Class ThemeConfig
 *
 * Content > Design > Configuration saves each field as its own config value
 * model. Magento\Theme\Model\Design\BackendModelFactory loads the stored row
 * for the saved scope into the model's orig data, and the field's backend
 * model has turned the posted value into the stored one (e.g. an image
 * uploader's file descriptor into its path) by the time SaveAfter runs, so
 * the model alone holds the old and new value for its path.
 *
 * @package MageOS\AdminActivityLog\Model\Activity
 */
class ThemeConfig implements ModelInterface
{
    /**
     * Placeholder for values that cannot be JSON encoded
     */
    private const UNSERIALIZABLE_VALUE = '[unserializable]';

    /**
     * Get the stored value of the theme configuration field before this save
     * @return array<string, string>
     */
    public function getOldData(DataObject $model): array
    {
        return [(string)$model->getData('path') => $this->normalizeValue($model->getOrigData('value'))];
    }

    /**
     * Get edit activity data of theme configuration
     * @param array<string, string> $fieldArray Form field names to skip, e.g. head_includes
     * @return array{}|array<string, array{
     *      old_value: string,
     *      new_value: string
     *  }>
     */
    public function getEditData(DataObject $model, array $fieldArray): array
    {
        $model->setId($model->getScope() . '/scope_id/' . $model->getScopeId());

        $path = (string)$model->getData('path');
        $fieldName = str_replace('/', '_', preg_replace('#^design/#', '', $path));
        if (in_array($fieldName, $fieldArray, true)) {
            return [];
        }

        $oldValue = $this->getOldData($model)[$path];
        $newValue = $this->normalizeValue($model->getData('value'));
        if ($newValue === $oldValue) {
            return [];
        }

        return [
            $path => [
                'old_value' => $oldValue,
                'new_value' => $newValue
            ]
        ];
    }

    /**
     * Normalize a config value to a string for comparison
     *
     * @param mixed $value Raw value
     * @return string Normalized string
     */
    private function normalizeValue(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? self::UNSERIALIZABLE_VALUE : $encoded;
    }
}
