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

namespace MageOS\AdminActivityLog\Api;

use Magento\User\Model\User;
use MageOS\AdminActivityLog\Model\ResourceModel\Login\Collection;

/**
 * Interface LoginRepositoryInterface
 *
 * @api
 */
interface LoginRepositoryInterface
{
    /**
     * Add login activity log entry
     *
     * @param int $status Login status (success/failure)
     * @param string $type Type of activity (Login/Logout)
     * @param string $remark Optional remark for the log entry
     * @return bool
     */
    public function addLog(int $status, string $type, string $remark = ''): bool;

    /**
     * Get all login activity data before specified date
     *
     * @param string $endDate Date in Y-m-d H:i:s format
     * @return Collection
     */
    public function getListBeforeDate(string $endDate): Collection;

    /**
     * Set the user for login activity tracking
     *
     * @param User $user
     * @return LoginRepositoryInterface
     */
    public function setUser(User $user): LoginRepositoryInterface;

    /**
     * Track successful login
     *
     * @return void
     */
    public function addSuccessLog(): void;

    /**
     * Track failed login attempt
     *
     * @param string $remark Optional remark for the failure
     * @return void
     */
    public function addFailedLog(string $remark = ''): void;

    /**
     * Track logout
     *
     * @return void
     */
    public function addLogoutLog(): void;
}
