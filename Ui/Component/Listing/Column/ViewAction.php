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

use Magento\Backend\Block\Widget\Button;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ViewAction
 * @package MageOS\AdminActivityLog\Ui\Component\Listing\Column
 */
class ViewAction extends Column
{
    /**
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected readonly UrlInterface $urlBuilder,
        protected readonly LayoutInterface $layout,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function getViewUrl(): string
    {
        $urlPath = $this->getData('config/viewUrlPath');
        return $this->urlBuilder->getUrl(
            is_string($urlPath) ? $urlPath : ''
        );
    }

    /**
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
                if (isset($item['entity_id'])) {
                    $entityId = (string)$item['entity_id'];
                    $item[$columnName] = $this->layout->createBlock(
                        Button::class,
                        '',
                        [
                            'data' => [
                                'label' => __('View'),
                                'type' => 'button',
                                'disabled' => false,
                                'class' => 'action-activity-log-view',
                                'onclick' => 'adminActivityLogView.open(\''
                                    . $this->getViewUrl() . '\', \'' . $entityId
                                    . '\')',
                            ]
                        ]
                    )->toHtml();
                }
            }
        }

        return $dataSource;
    }
}
