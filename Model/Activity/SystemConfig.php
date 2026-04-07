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
        $parts = explode('/', $path);
        $sectionId = $parts[0];

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
        $model->setConfig('System Configuration');
        $model->setId($path);

        $oldGroups = $model->getOrigData() ?? [];
        $newGroups = $model->getGroups() ?? [];

        foreach ($newGroups as $group => $groupData) {
            if (empty($groupData['fields'])) {
                continue;
            }
            foreach ($groupData['fields'] as $field => $fieldData) {
                $newRaw = $fieldData['value'] ?? '';
                $oldRaw = $oldGroups[$group]['fields'][$field]['value'] ?? '';
                $newValue = is_array($newRaw) ? implode(',', $newRaw) : (string)$newRaw;
                $oldValue = is_array($oldRaw) ? implode(',', $oldRaw) : (string)$oldRaw;
                if ($newValue === $oldValue) {
                    continue;
                }
                $fieldPath = implode('/', [$path, $group, $field]);
                $logData[$fieldPath] = [
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ];
            }
        }

        return $logData;
    }
}
