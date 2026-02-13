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

namespace MageOS\AdminActivityLog\Ui\Component\Listing\Column;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ItemColumn
 * @package MageOS\AdminActivityLog\Ui\Component\Listing\Column
 */
class ItemColumn extends Column
{
    public const URL_COUNT = 7;

    /**
     * @var string[]
     */
    protected array $allowedAttributes = [
        'href',
        'title',
        'id',
        'class',
        'style',
        'target'
    ];

    protected readonly Escaper $escaper;
    protected readonly FilterManager $filterManager;

    /**
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        Context $contexts,
        UiComponentFactory $uiComponentFactory,
        protected readonly UrlInterface $backendUrl,
        array $components,
        array $data
    ) {
        $this->escaper = $contexts->getEscaper();
        $this->filterManager = $contexts->getFilterManager();
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param string|array<int|string, string> $data
     * @param string[]|null $allowedTags
     * @return string|array<int|string, string>
     */
    public function escapeHtml(array|string $data, ?array $allowedTags = null): string|array
    {
        return $this->escaper->escapeHtml($data, $allowedTags);
    }

    /**
     * Render block HTML
     */
    public function _toHtml(): string
    {
        $length = 30;
        $label = $this->getData('label');
        $itemName = $this->filterManager->truncate(
            is_string($label) ? $label : '',
            ['length' => $length, 'etc' => '...', 'remainder' => '', 'breakWords' => false]
        );
        $escaped = $this->escapeHtml($itemName);
        return '<a ' . $this->getLinkAttributes() . ' >' . $escaped . '</a>';
    }

    /**
     * Prepare link attributes as serialized and formatted string
     */
    public function getLinkAttributes(): string
    {
        $attributes = [];
        foreach ($this->allowedAttributes as $attribute) {
            $value = $this->getDataUsingMethod($attribute);
            if ($value !== null) {
                $attributes[$attribute] = $this->escapeHtml($value);
            }
        }

        if (!empty($attributes)) {
            return $this->serialize($attributes);
        }

        return '';
    }

    /**
     * Serialize attributes
     * @param array<string, string> $keys
     * @param string $valueSeparator
     * @param string $fieldSeparator
     * @param string $quote
     */
    public function serialize(
        $keys = [],
        $valueSeparator = '=',
        $fieldSeparator = ' ',
        $quote = '"'
    ) {
        $data = [];
        foreach ($keys as $key => $value) {
            $data[] = $key . $valueSeparator . $quote . $value . $quote;
        }
        return implode($fieldSeparator, $data);
    }

    /**
     * Convert action to url
     */
    public function prepareUrl(string $url): string
    {
        $parts = explode('/', $url);
        if ((isset($parts[0]) && $parts[0] === 'theme') && count($parts) === self::URL_COUNT) {
            [$module, $controller, $action, $scope, $store, $field, $id] = $parts;
            return $this->backendUrl->getUrl(
                implode('/', [$module, $controller, $action, $scope, $store]),
                [$field => $id]
            );
        }
        if (count($parts) < 5) {
            return '';
        }
        [$module, $controller, $action, $field, $id] = $parts;

        return $this->backendUrl->getUrl(
            implode('/', [$module, $controller, $action]),
            [$field => $id]
        );
    }

    /**
     * Initialize parameter for link
     * @param array<string, string> $item
     */
    protected function initLinkParams(array $item): void
    {
        $this->setData('href', $this->prepareUrl($item['item_url']));
        $this->setData('title', $item['item_name']);
        $this->setData('target', '_blank');
        $this->setData('label', $item['item_name']);
    }

    /**
     * Prepare Data Source
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items']) && is_array($dataSource['data']['items'])) {
            $name = $this->getData('name');
            $columnName = is_string($name) ? $name : '';
            foreach ($dataSource['data']['items'] as &$item) {
                if (!is_array($item)) {
                    continue;
                }
                if (!empty($item['item_url'])) {
                    /** @var array<string, string> $item */
                    $this->initLinkParams($item);
                    $item[$columnName] = $this->_toHtml();
                }
            }
        }

        return $dataSource;
    }
}
