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

namespace MageOS\AdminActivityLog\Cron;

use Exception;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MageOS\AdminActivityLog\Api\ActivityRepositoryInterface;
use MageOS\AdminActivityLog\Api\LoginRepositoryInterface;
use MageOS\AdminActivityLog\Helper\Data as Helper;
use Psr\Log\LoggerInterface;

/**
 * Class ClearLog
 * @package MageOS\AdminActivityLog\Cron
 */
class ClearLog
{
    /**
     * Default date format
     */
    protected const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly DateTime $dateTime,
        protected readonly Helper $helper,
        protected readonly ActivityRepositoryInterface $activityRepository,
        protected readonly LoginRepositoryInterface $loginRepository
    ) {
    }

    /**
     * Return cron cleanup date
     * @return null|string
     */
    public function __getDate()
    {
        $timestamp = $this->dateTime->gmtTimestamp();
        $day = $this->helper->getConfigValue('CLEAR_LOG_DAYS');
        if ($day) {
            $timestamp -= $day * 24 * 60 * 60;
            return $this->dateTime->gmtDate(self::DATE_FORMAT, $timestamp);
        }
        return null;
    }

    /**
     * Delete record which date is less than the current date
     * @return void
     */
    public function execute(): void
    {
        try {
            if (!$this->helper->isEnable()) {
                return;
            }

            if ($date = $this->__getDate()) {
                $activities = $this->activityRepository->getListBeforeDate($date);
                $this->deleteActivities($activities);

                // Remove login activity detail
                if ($this->helper->isLoginEnable()) {
                    $activities = $this->loginRepository->getListBeforeDate($date);
                    $this->deleteActivities($activities);
                }
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Remove activity detail
     * @param Iterable $activities
     * @return void
     */
    protected function deleteActivities($activities): void
    {
        if (empty($activities)) {
            return;
        }

        foreach ($activities as $activity) {
            $activity->delete();
        }
    }
}
