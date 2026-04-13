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

namespace MageOS\AdminActivityLog\Test\Unit\Plugin\User;

use Magento\Framework\Model\AbstractModel;
use Magento\User\Model\ResourceModel\User;
use MageOS\AdminActivityLog\Plugin\User\DeletePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeletePluginTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private User&MockObject $subject;
    private DeletePlugin $plugin;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subject = $this->createMock(User::class);

        $this->plugin = new DeletePlugin($this->logger);
    }

    public function testBeforeDeleteReloadsUserModel(): void
    {
        $user = $this->createMock(AbstractModel::class);
        $user->method('getId')->willReturn(42);

        $user->expects($this->once())
            ->method('load')
            ->with(42);

        $this->plugin->beforeDelete($this->subject, $user);
    }

    public function testBeforeDeleteSwallowsLoadException(): void
    {
        $user = $this->createMock(AbstractModel::class);
        $user->method('getId')->willReturn(42);
        $user->method('load')
            ->willThrowException(new \RuntimeException('Table not found'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Admin activity pre-delete user load failed',
                $this->callback(function (array $context): bool {
                    return $context['user_id'] === 42
                        && str_contains($context['exception'], 'Table not found');
                })
            );

        // Must not throw — user deletion must proceed
        $this->plugin->beforeDelete($this->subject, $user);
    }

    public function testBeforeDeleteSwallowsThrowableErrors(): void
    {
        $user = $this->createMock(AbstractModel::class);
        $user->method('getId')->willReturn(7);
        $user->method('load')
            ->willThrowException(new \Error('Memory limit exceeded'));

        $this->logger->expects($this->once())
            ->method('error');

        $this->plugin->beforeDelete($this->subject, $user);
    }

    public function testNoErrorLoggedOnSuccess(): void
    {
        $user = $this->createMock(AbstractModel::class);
        $user->method('getId')->willReturn(1);

        $this->logger->expects($this->never())
            ->method('error');

        $this->plugin->beforeDelete($this->subject, $user);
    }
}
