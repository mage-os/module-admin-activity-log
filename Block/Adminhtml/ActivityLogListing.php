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

namespace MageOS\AdminActivityLog\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use MageOS\AdminActivityLog\Api\ActivityRepositoryInterface;
use MageOS\AdminActivityLog\Helper\Browser;

/**
 * Class ActivityLogListing
 * @package MageOS\AdminActivityLog\Block\Adminhtml
 */
class ActivityLogListing extends Template
{
    protected $_template = 'MageOS_AdminActivityLog::log_listing.phtml';

    public function __construct(
        Context $context,
        protected readonly ActivityRepositoryInterface $activityRepository,
        protected readonly Browser $browser,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct(
            $context,
            $data,
            $jsonHelper,
            $directoryHelper
        );
    }

    /**
     * Get admin activity log listing
     *
     * @return null|array<int, array{
     *     entity_id: string,
     *     activity_id: string,
     *     field_name: string,
     *     old_value: string,
     *     new_value: string
     * }>
     */
    public function getLogListing(): ?array
    {
        $id = $this->getRequest()->getParam('id');
        $data = $this->activityRepository->getActivityLog($id);
        return $data->getData();
    }

    /**
     * @return array<string, string>
     * @throws NoSuchEntityException
     */
    public function getAdminDetails(): array
    {
        $id = $this->getRequest()->getParam('id');
        $activity = $this->activityRepository->getActivityById($id);

        $this->browser->reset();
        $this->browser->setUserAgent($activity->getUserAgent());
        $browser = $this->browser->__toString();

        $store = $this->_storeManager->getStore($activity->getStoreId());
        if ($store->getId() == 0) {
            $storeViewName = 'Default Config';
        } else {
            $storeViewName = sprintf('%s > %s > %s', $store->getWebsite()->getName(), $store->getGroup()->getName(), $store->getName());
        }

        return [
            'username' => $activity->getUsername(),
            'module' => $activity->getModule(),
            'name' => $activity->getName(),
            'fullaction' => $activity->getFullaction(),
            'path' => $activity->getItemPath(),
            'scope' => $activity->getScope(),
            'store_name' => $storeViewName,
            'browser' => $browser,
            'date' => $activity->getUpdatedAt()
        ];
    }

    public function getActivityRepository(): ActivityRepositoryInterface
    {
        return $this->activityRepository;
    }
}
