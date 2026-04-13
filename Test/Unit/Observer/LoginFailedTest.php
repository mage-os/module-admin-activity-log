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

namespace MageOS\AdminActivityLog\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\User\Model\User;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Api\LoginRepositoryInterface;
use MageOS\AdminActivityLog\Observer\LoginFailed;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoginFailedTest extends TestCase
{
    private ActivityConfigInterface&MockObject $activityConfig;
    private LoggerInterface&MockObject $logger;
    private User&MockObject $user;
    private LoginRepositoryInterface&MockObject $loginRepository;
    private LoginFailed $subject;

    protected function setUp(): void
    {
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->user = $this->createMock(User::class);
        $this->loginRepository = $this->createMock(LoginRepositoryInterface::class);

        $this->subject = new LoginFailed(
            $this->activityConfig,
            $this->logger,
            $this->user,
            $this->loginRepository
        );
    }

    public function testIsEnabledDelegatesToIsLoginEnabled(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(false);

        $observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getUserName', 'getException'])
            ->getMock();

        $this->loginRepository->expects($this->never())->method('setUser');

        $this->subject->execute($observer);
    }

    public function testProcessLoadsUserByUsernameAndLogsFailure(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(true);

        $exception = new \Exception('Invalid password');

        $observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getUserName', 'getException'])
            ->getMock();
        $observer->method('getUserName')->willReturn('admin');
        $observer->method('getException')->willReturn($exception);

        $loadedUser = $this->createMock(User::class);
        $this->user->expects($this->once())
            ->method('setUserName')
            ->with('admin');
        $this->user->expects($this->once())
            ->method('loadByUsername')
            ->with('admin')
            ->willReturn($loadedUser);

        $this->loginRepository->expects($this->once())
            ->method('setUser')
            ->with($loadedUser)
            ->willReturn($this->loginRepository);

        $this->loginRepository->expects($this->once())
            ->method('addFailedLog')
            ->with('Invalid password');

        $this->subject->execute($observer);
    }

    public function testProcessHandlesNullUsername(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(true);

        $exception = new \Exception('Empty credentials');

        $observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getUserName', 'getException'])
            ->getMock();
        $observer->method('getUserName')->willReturn(null);
        $observer->method('getException')->willReturn($exception);

        $this->user->expects($this->once())
            ->method('setUserName')
            ->with(null);
        $this->user->expects($this->never())
            ->method('loadByUsername');

        $this->loginRepository->expects($this->once())
            ->method('setUser')
            ->with($this->user)
            ->willReturn($this->loginRepository);

        $this->loginRepository->expects($this->once())
            ->method('addFailedLog')
            ->with('Empty credentials');

        $this->subject->execute($observer);
    }
}
