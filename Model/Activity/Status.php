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

use MageOS\AdminActivityLog\Model\ActivityFactory;

/**
 * Class Status
 * @package MageOS\AdminActivityLog\Model\Activity
 */
class Status
{
    public const ACTIVITY_NONE = 0;
    public const ACTIVITY_REVERTABLE = 1;
    public const ACTIVITY_REVERT_SUCCESS = 2;
    public const ACTIVITY_FAIL = 3;

    public function __construct(
        protected readonly ActivityFactory $activityFactory
    ) {
    }

    /**
     * Set success revert status
     */
    public function markSuccess(int $activityId): void
    {
        $activityModel = $this->activityFactory->create()->load($activityId);
        // After successful revert, activity is no longer revertable
        $activityModel->setIsRevertable(false);
        $activityModel->save();
    }

    /**
     * Set fail revert status
     */
    public function markFail(int $activityId): void
    {
        $activityModel = $this->activityFactory->create()->load($activityId);
        // Revert attempt failed; keep activity revertable
        $activityModel->setIsRevertable(true);
        $activityModel->save();
    }
}
