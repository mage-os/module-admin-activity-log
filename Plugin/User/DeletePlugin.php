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

namespace MageOS\AdminActivityLog\Plugin\User;

use Magento\Framework\Model\AbstractModel;
use Magento\User\Model\ResourceModel\User;
use MageOS\AdminActivityLog\Helper\Benchmark;

/**
 * Class Delete
 * @package MageOS\AdminActivityLog\Plugin\User
 */
class DeletePlugin
{
    public function __construct(
        private readonly Benchmark $benchmark
    ) {
    }

    public function aroundDelete(User $subject, callable $proceed, AbstractModel $user): bool
    {
        $this->benchmark->start(__METHOD__);
        $user->load($user->getId());

        /** @var bool $result */
        $result = $proceed($user);
        $user->afterDelete();

        $this->benchmark->end(__METHOD__);
        return $result;
    }
}
