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

use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Api\Activity\ModelInterface;

/**
 * Class ThemeConfig
 * @package MageOS\AdminActivityLog\Model\Activity
 */
class ThemeConfig implements ModelInterface
{
    /**
     * Placeholder for values that cannot be JSON encoded
     */
    private const UNSERIALIZABLE_VALUE = '[unserializable]';

    public function __construct(
        protected readonly DataObject $dataObject,
        protected readonly ConfigCollectionFactory $configCollectionFactory,
        protected readonly RequestInterface $request
    ) {
    }

    /**
     * Get config path of theme configuration
     */
    public function getPath(DataObject $model): string
    {
        if ($model->getData('path')) {
            return current(
                explode(
                    '/',
                    (string)$model->getData('path')
                )
            );
        }
        return '';
    }

    /**
     * Get old activity data of theme configuration
     * @return array<string, string>
     */
    public function getOldData(DataObject $model): array
    {
        $path = $this->getPath($model);
        /** @var ConfigCollection $systemDataCollection */
        $systemDataCollection = $this->configCollectionFactory->create();
        $systemDataCollection->addFieldToFilter('path', ['like' => $path . '/%']);

        $data = [];
        foreach ($systemDataCollection->getData() as $config) {
            $path = str_replace('design_', '', str_replace('/', '_', $config['path']));
            $data[$path] = $config['value'];
        }

        return $data;
    }

    /**
     * Get edit activity data of theme configuration
     * @param array<string, string> $fieldArray
     * @return array{}|array<string, array{
     *      old_value: string,
     *      new_value: string
     *  }>
     */
    public function getEditData(DataObject $model, array $fieldArray): array
    {
        $path = 'stores/scope_id/' . $model->getScopeId();
        $oldData = $this->getOldData($model);
        $newData = $this->request->getPostValue();
        $result = $this->collectAdditionalData($oldData, $newData, $fieldArray);
        $model->setId($path);
        return $result;
    }

    /**
     * Set additional data
     * @param array<string, string> $oldData
     * @param array<string, string> $newData
     * @param array<string, string> $fieldArray
     * @return array{}|array<string, array{
     *     old_value: string,
     *     new_value: string
     * }>
     */
    public function collectAdditionalData(array $oldData, array $newData, array $fieldArray): array
    {
        $logData = [];
        foreach ($newData as $key => $value) {
            if (in_array($key, $fieldArray)) {
                continue;
            }
            $newValue = $this->normalizeValue($value);
            $oldValue = $this->normalizeValue($oldData[$key] ?? null);

            if ($newValue !== $oldValue) {
                $key = 'design/' . preg_replace('/_/', '/', (string)$key, 1);
                $logData[$key] = [
                    'old_value' => $oldValue,
                    'new_value' => $newValue
                ];
            }
        }

        return $logData;
    }

    /**
     * Normalize a value to a string for comparison
     *
     * @param mixed $value Raw value
     * @return string Normalized string
     */
    private function normalizeValue(mixed $value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }

        if (is_array($value)) {
            return $this->flattenPostedValue($value);
        }

        return (string)$value;
    }

    /**
     * Flatten an array posted by the design config form to a comparable string
     *
     * Image uploader fields (favicon, logos) post a list of file descriptors
     * rather than a scalar; Theme\Model\Design\Backend\File::afterLoad() puts the
     * stored config value in the descriptor's "file" key and its basename in
     * "name", so prefer "file" to compare against core_config_data.
     *
     * Deliberately not the same as the sibling SystemConfig::flattenValue():
     * that one sees backend model values rather than posted form data and has
     * no file descriptors to unwrap.
     *
     * @param array<mixed> $value Raw value
     * @return string Normalized string
     */
    private function flattenPostedValue(array $value): string
    {
        if (!array_is_list($value)) {
            return $this->encodeValue($value);
        }

        $parts = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                $parts[] = is_scalar($item) ? (string)$item : $this->encodeValue($item);
                continue;
            }

            $file = $item['file'] ?? $item['name'] ?? null;
            $parts[] = is_scalar($file) ? (string)$file : $this->encodeValue($item);
        }

        return implode(',', $parts);
    }

    /**
     * Encode a value that has no meaningful scalar representation
     *
     * @param mixed $value Raw value
     * @return string Encoded value, or a placeholder if it cannot be encoded
     */
    private function encodeValue(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? self::UNSERIALIZABLE_VALUE : $encoded;
    }
}
