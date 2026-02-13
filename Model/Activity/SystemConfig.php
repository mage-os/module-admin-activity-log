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

use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Group;
use Magento\Config\Model\Config\Structure\Element\Section;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Api\Activity\ModelInterface;

/**
 * Class SystemConfig
 * @package MageOS\AdminActivityLog\Model\Activity
 */
class SystemConfig implements ModelInterface
{
    public const MODULE_SYSTEM_CONFIGURATION = 'system_configuration';

    public function __construct(
        protected readonly DataObject $dataObject,
        protected readonly ValueFactory $valueFactory,
        private readonly Structure $configStructure
    ) {
    }

    /**
     * Get config path
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

    public function getHumanReadablePath(string $path): string
    {
        $labels = [__('System Configuration')];
        [$sectionId, $groupId, $fieldId] = explode('/', $path);

        $section = $this->configStructure->getElement($sectionId);
        if (!$section instanceof Section) {
            return $path;
        }

        $tabId = $section->getAttribute('tab');
        if ($tabId) {
            foreach ($this->configStructure->getTabs() as $tab) {
                if ($tab->getId() !== $tabId) {
                    continue;
                }

                $labels[] = $tab->getLabel();
            }
        }

        $labels[] = $section->getLabel();
        foreach ($section->getChildren() as $group) {
            if (!$group instanceof Group || $group->getId() !== $groupId) {
                continue;
            }

            $labels[] = $group->getLabel();
            foreach ($group->getChildren() as $field) {
                if ($field->getId() !== $fieldId) {
                    continue;
                }

                $labels[] = $field->getLabel();
            }
        }

        return implode(' > ', $labels);
    }

    /**
     * Get old activity data of system config module
     */
    public function getOldData(DataObject $model): DataObject
    {
        $path = $this->getPath($model);
        $systemData = $this->valueFactory->create()->getCollection()->addFieldToFilter(
            'path',
            ['like' => $path . '/%']
        );
        $data = [];
        foreach ($systemData->getData() as $config) {
            $splittedPath = explode('/', (string)$config['path']);
            if (count($splittedPath) === 2) {
                [$group, $field] = explode('/', (string)$config['path']);
            } else {
                [$path, $group, $field] = explode('/', (string)$config['path']);
            }

            $data[$group]['fields'][$field]['value'] = $config['value'];
        }

        return $this->dataObject->setData($data);
    }

    /**
     * Get edit activity data of system config module
     * @param DataObject $model
     * @param array $fieldArray
     * @return array{}|array<string, array{
     *     old_value: mixed,
     *     new_value: mixed
     * }>
     */
    public function getEditData(DataObject $model, $fieldArray): array
    {
        $logData = [];

        $path = $this->getPath($model);
        $result = $this->collectAdditionalData(
            $model->getOrigData() ?? [],
            $model->getGroups() ?? []
        );

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
