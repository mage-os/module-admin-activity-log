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
     * @var array
     */
    protected $allowedAttributes = [
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
     * ItemColumn constructor.
     * @param ContextInterface $context
     * @param Context $contexts
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $backendUrl
     * @param array $components
     * @param array $data
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
     * Escape HTML entities
     * @param string|array $data
     * @param array|null $allowedTags
     * @return string
     */
    public function escapeHtml($data, ?array $allowedTags = null): string
    {
        return $this->escaper->escapeHtml($data, $allowedTags);
    }

    /**
     * Render block HTML
     * @return string
     */
    public function _toHtml(): string
    {
        $length = 30;
        $itemName = $this->filterManager->truncate(
            $this->getLabel(),
            ['length' => $length, 'etc' => '...', 'remainder' => '', 'breakWords' => false]
        );
        return '<a ' . $this->getLinkAttributes() . ' >' . $this->escapeHtml($itemName) . '</a></li>';
    }

    /**
     * Prepare link attributes as serialized and formatted string
     * @return string
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
     * @param array $keys
     * @param string $valueSeparator
     * @param string $fieldSeparator
     * @param string $quote
     * @return  string
     */
    #[\Override]
    public function serialize($keys = [], $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"'): string
    {
        $data = [];
        foreach ($keys as $key => $value) {
            $data[] = $key . $valueSeparator . $quote . $value . $quote;
        }
        return implode($fieldSeparator, $data);
    }

    /**
     * Convert action to url
     * @param string $url
     * @return string
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
        [$module, $controller, $action, $field, $id] = $parts;

        return $this->backendUrl->getUrl(
            implode('/', [$module, $controller, $action]),
            [$field => $id]
        );
    }

    /**
     * Initialize parameter for link
     * @param array $item
     * @return void
     */
    protected function initLinkParams(array $item): void
    {
        $this->setHref($this->prepareUrl($item['item_url']));
        $this->setTitle($item['item_name']);
        $this->setTarget('_blank');
        $this->setLabel($item['item_name']);
    }

    /**
     * Prepare Data Source
     * @param array $dataSource
     * @return array
     */
    #[\Override]
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!empty($item['item_url'])) {
                    $this->initLinkParams($item);
                    $item[$this->getData('name')] = $this->_toHtml();
                }
            }
        }

        return $dataSource;
    }
}
