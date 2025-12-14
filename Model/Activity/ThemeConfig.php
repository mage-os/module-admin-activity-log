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

use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Api\Activity\ModelInterface;
use MageOS\AdminActivityLog\Model\ResourceModel\ActivityLog\Collection;

/**
 * Class ThemeConfig
 * @package MageOS\AdminActivityLog\Model\Activity
 */
class ThemeConfig implements ModelInterface
{
    public function __construct(
        protected readonly DataObject $dataObject,
        protected readonly ConfigCollectionFactory $configCollectionFactory,
        protected readonly RequestInterface $request,
        protected readonly WriterInterface $configWriter
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
        $model->setConfig('Theme Configuration');
        $model->setId($path);
        return $result;
    }

    /**
     * Get revert activity data of theme configuration
     */
    public function revertData(Collection $logData, int|string $scopeId, string $scope): bool
    {
        foreach ($logData as $log) {
            $this->configWriter->save(
                $log->getFieldName(),
                $log->getOldValue(),
                $scope,
                $scopeId
            );
        }
        return true;
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
            $newValue = !empty($value) ? $value : '';
            $oldValue = !empty($oldData[$key]) ? $oldData[$key] : '';

            if ($newValue != $oldValue) {
                $key = 'design/' . preg_replace('/_/', '/', (string) $key, 1);
                $logData[$key] = [
                    'old_value' => $oldValue,
                    'new_value' => $newValue
                ];
            }
        }

        return $logData;
    }
}
