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

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use MageOS\AdminActivityLog\Helper\Browser;

/**
 * Class UserAgentColumn
 * @package MageOS\AdminActivityLog\Ui\Component\Listing\Column
 */
class UserAgentColumn extends Column
{
    /**
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected readonly Browser $browser,
        array $components,
        array $data
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Get user agent data
     * @param array<string, string> $item
     */
    public function getAgent(array $item): string
    {
        $this->browser->reset();
        $this->browser->setUserAgent($item['user_agent']);
        return $this->browser->__toString();
    }

    /**
     * Prepare Data Source
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->getAgent($item);
            }
        }

        return $dataSource;
    }
}
