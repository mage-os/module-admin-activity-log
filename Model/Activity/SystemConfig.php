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

namespace MageOS\AdminActivityLog\Model\Activity;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Api\Activity\ModelInterface;

/**
 * Class SystemConfig
 * @package MageOS\AdminActivityLog\Model\Activity
 */
class SystemConfig implements ModelInterface
{
    /**
     * SystemConfig constructor.
     * @param DataObject $dataObject
     * @param ValueFactory $valueFactory
     * @param WriterInterface $configWriter
     */
    public function __construct(
        protected readonly DataObject $dataObject,
        protected readonly ValueFactory $valueFactory,
        protected readonly WriterInterface $configWriter
    ) {
    }

    /**
     * Get config path
     * @param $model
     * @return string
     */
    public function getPath($model): string
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
     * Get old activity data of system config module
     * @param DataObject $model
     * @return mixed
     */
    public function getOldData(DataObject $model)
    {
        $path = $this->getPath($model);
        $systemData = $this->valueFactory->create()->getCollection()->addFieldToFilter(
            'path',
            ['like' => $path . '/%']
        );
        $data = [];
        foreach ($systemData->getData() as $config) {
            $splittedPath = explode('/', (string) $config['path']);
            if (count($splittedPath) === 2) {
                [$group, $field] = explode('/', (string) $config['path']);
            } else {
                [$path, $group, $field] = explode('/', (string) $config['path']);
            }

            $data[$group]['fields'][$field]['value'] = $config['value'];
        }

        return $this->dataObject->setData($data);
    }

    /**
     * Get edit activity data of system config module
     * @param DataObject $model
     * @param array $fieldArray
     * @return array{old_value: mixed, new_value: mixed}[]
     */
    public function getEditData(DataObject $model, $fieldArray): array
    {
        $logData = [];

        $path = $this->getPath($model);
        $result = $this->collectAdditionalData($model->getOrigData(), $model->getGroups());

        $model->setConfig('System Configuration');
        $model->setId($path);

        if (!empty($result) && is_array($result)) {
            foreach ($result as $group => $fields) {
                if (!empty($fields['fields'])) {
                    foreach ($fields['fields'] as $field => $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $fieldPath = implode('/', [
                            $path,
                            $group,
                            $field
                        ]);

                        $logData[$fieldPath] = [
                            'old_value' => $value['old_value'],
                            'new_value' => $value['new_value']
                        ];
                    }
                }
            }
        }

        return $logData;
    }

    /**
     * Get revert activity data of system config module
     * @param Iterable $logData
     * @param mixed $scopeId
     * @return bool
     */
    public function revertData($logData, $scopeId): bool
    {
        if (!empty($logData)) {
            foreach ($logData as $log) {
                $this->configWriter->save(
                    $log->getFieldName(),
                    $log->getOldValue(),
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    $scopeId
                );
            }
        }
        return true;
    }

    /**
     * Set additional data
     * @param array $oldData
     * @param mixed $newData
     * @return array
     */
    protected function collectAdditionalData(array $oldData, array $newData): array
    {
        $result = [];
        if (!empty($oldData) && is_array($oldData)) {
            foreach ($oldData as $key => $value) {
                if (isset($newData[$key])) {
                    if (is_array($value)) {
                        $result[$key] = $this->collectAdditionalData($value, $newData[$key]);
                    } elseif (!is_array($newData[$key]) && (string)$value != (string)$newData[$key]) {
                        $result['new_value'] = (string)$newData[$key];
                        $result['old_value'] = $value;
                    }
                }
            }
        }
        return $result;
    }
}
