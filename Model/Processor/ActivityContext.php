<?php
/**
 * MageOS
 *
 * @category   MageOS
 * @package    MageOS_AdminActivityLog
 * @copyright  Copyright (C) 2025 MageOS (https://mage-os.org/)
 * @license    https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace MageOS\AdminActivityLog\Model\Processor;

use Magento\Framework\App\ResourceConnection;
use MageOS\AdminActivityLog\Model\Activity;
use MageOS\AdminActivityLog\Model\ActivityFactory;
use Psr\Log\LoggerInterface;

/**
 * Context object aggregating activity logging dependencies for Processor
 *
 * This class reduces constructor parameters in the Processor class by
 * grouping activity-related services together.
 */
class ActivityContext
{
    public function __construct(
        private readonly ActivityFactory $activityFactory,
        private readonly LoggerInterface $logger,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    public function createActivity(): Activity
    {
        return $this->activityFactory->create();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getResourceConnection(): ResourceConnection
    {
        return $this->resourceConnection;
    }

    /**
     * Get database connection
     */
    public function getConnection(): \Magento\Framework\DB\Adapter\AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * Get table name with prefix
     */
    public function getTableName(string $tableName): string
    {
        return $this->resourceConnection->getTableName($tableName);
    }
}
