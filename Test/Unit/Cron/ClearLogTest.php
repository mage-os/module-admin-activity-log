<?php

declare(strict_types=1);

namespace MageOS\AdminActivityLog\Test\Unit\Cron;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Cron\ClearLog;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ClearLogTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private DateTime&MockObject $dateTime;
    private ActivityConfigInterface&MockObject $activityConfig;
    private ResourceConnection&MockObject $resourceConnection;
    private AdapterInterface&MockObject $connection;
    private Select&MockObject $select;
    private ClearLog $subject;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dateTime = $this->createMock(DateTime::class);
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->select = $this->createMock(Select::class);

        $this->resourceConnection->method('getConnection')
            ->willReturn($this->connection);
        $this->resourceConnection->method('getTableName')
            ->willReturnArgument(0);

        $this->connection->method('select')
            ->willReturn($this->select);
        $this->select->method('from')
            ->willReturnSelf();
        $this->select->method('where')
            ->willReturnSelf();
        $this->select->method('limit')
            ->willReturnSelf();

        $this->subject = new ClearLog(
            $this->logger,
            $this->dateTime,
            $this->activityConfig,
            $this->resourceConnection
        );
    }

    public function testGetCleanupDateReturnsNullWhenDaysIsZero(): void
    {
        $this->activityConfig->method('getClearLogDays')
            ->willReturn(0);

        $this->assertNull($this->subject->getCleanupDate());
    }

    public function testGetCleanupDateReturnsFormattedDate(): void
    {
        $timestamp = 1700000000;
        $days = 30;
        $expectedTimestamp = $timestamp - ($days * 24 * 60 * 60);
        $expectedDate = '2023-10-16 11:33:20';

        $this->dateTime->method('gmtTimestamp')
            ->willReturn($timestamp);
        $this->activityConfig->method('getClearLogDays')
            ->willReturn($days);
        $this->dateTime->method('gmtDate')
            ->with('Y-m-d H:i:s', $expectedTimestamp)
            ->willReturn($expectedDate);

        $result = $this->subject->getCleanupDate();

        $this->assertSame($expectedDate, $result);
    }

    public function testExecuteReturnsEarlyWhenDisabled(): void
    {
        $this->activityConfig->method('isEnabled')
            ->willReturn(false);

        $this->connection->expects($this->never())
            ->method('fetchCol');
        $this->connection->expects($this->never())
            ->method('delete');

        $this->subject->execute();
    }

    public function testExecuteReturnsEarlyWhenCleanupDateIsNull(): void
    {
        $this->activityConfig->method('isEnabled')
            ->willReturn(true);
        $this->activityConfig->method('getClearLogDays')
            ->willReturn(0);

        $this->connection->expects($this->never())
            ->method('fetchCol');
        $this->connection->expects($this->never())
            ->method('delete');

        $this->subject->execute();
    }

    public function testExecuteDeletesActivityLogsInBatches(): void
    {
        $this->activityConfig->method('isEnabled')
            ->willReturn(true);
        $this->activityConfig->method('isLoginEnabled')
            ->willReturn(false);
        $this->activityConfig->method('getClearLogDays')
            ->willReturn(30);

        $this->dateTime->method('gmtTimestamp')
            ->willReturn(1700000000);
        $this->dateTime->method('gmtDate')
            ->willReturn('2023-10-16 11:33:20');

        $ids = [1, 2, 3, 4, 5];

        $this->connection->expects($this->once())
            ->method('fetchCol')
            ->willReturn($ids);

        $this->connection->expects($this->once())
            ->method('delete')
            ->with('admin_activity', ['entity_id IN (?)' => $ids])
            ->willReturn(count($ids));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cleared 5 records from admin_activity');

        $this->subject->execute();
    }

    public function testExecuteDeletesLoginLogsWhenLoginEnabled(): void
    {
        $this->activityConfig->method('isEnabled')
            ->willReturn(true);
        $this->activityConfig->method('isLoginEnabled')
            ->willReturn(true);
        $this->activityConfig->method('getClearLogDays')
            ->willReturn(30);

        $this->dateTime->method('gmtTimestamp')
            ->willReturn(1700000000);
        $this->dateTime->method('gmtDate')
            ->willReturn('2023-10-16 11:33:20');

        $activityIds = [1, 2, 3];
        $loginIds = [10, 11];

        $this->connection->expects($this->exactly(2))
            ->method('fetchCol')
            ->willReturnOnConsecutiveCalls($activityIds, $loginIds);

        $this->connection->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(function (string $table, array $condition) use ($activityIds, $loginIds): int {
                if ($table === 'admin_activity') {
                    $this->assertSame($activityIds, $condition['entity_id IN (?)']);
                    return count($activityIds);
                }
                if ($table === 'admin_login_log') {
                    $this->assertSame($loginIds, $condition['entity_id IN (?)']);
                    return count($loginIds);
                }
                $this->fail("Unexpected table: {$table}");
            });

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $this->subject->execute();
    }

    public function testExecuteHandlesEmptyTablesGracefully(): void
    {
        $this->activityConfig->method('isEnabled')
            ->willReturn(true);
        $this->activityConfig->method('isLoginEnabled')
            ->willReturn(true);
        $this->activityConfig->method('getClearLogDays')
            ->willReturn(30);

        $this->dateTime->method('gmtTimestamp')
            ->willReturn(1700000000);
        $this->dateTime->method('gmtDate')
            ->willReturn('2023-10-16 11:33:20');

        $this->connection->method('fetchCol')
            ->willReturn([]);

        $this->connection->expects($this->never())
            ->method('delete');

        $this->logger->expects($this->never())
            ->method('info');

        $this->subject->execute();
    }

    public function testExecuteLogsExceptionOnFailure(): void
    {
        $this->activityConfig->method('isEnabled')
            ->willReturn(true);
        $this->activityConfig->method('getClearLogDays')
            ->willReturn(30);

        $this->dateTime->method('gmtTimestamp')
            ->willReturn(1700000000);
        $this->dateTime->method('gmtDate')
            ->willReturn('2023-10-16 11:33:20');

        $exception = new Exception('Database connection lost');

        $this->connection->method('fetchCol')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Failed to clear admin activity logs',
                $this->callback(function (array $context) {
                    return $context['exception'] === 'Database connection lost'
                        && isset($context['trace']);
                })
            );

        $this->subject->execute();
    }
}
