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
use MageOS\AdminActivityLog\Observer\LoginSuccess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoginSuccessTest extends TestCase
{
    private ActivityConfigInterface&MockObject $activityConfig;
    private LoggerInterface&MockObject $logger;
    private LoginRepositoryInterface&MockObject $loginRepository;
    private LoginSuccess $subject;

    protected function setUp(): void
    {
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->loginRepository = $this->createMock(LoginRepositoryInterface::class);

        $this->subject = new LoginSuccess(
            $this->activityConfig,
            $this->logger,
            $this->loginRepository
        );
    }

    public function testIsEnabledDelegatesToIsLoginEnabled(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(false);

        $observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getUser'])
            ->getMock();

        $this->loginRepository->expects($this->never())->method('setUser');

        $this->subject->execute($observer);
    }

    public function testProcessSetsUserAndCallsAddSuccessLog(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(true);

        $user = $this->createMock(User::class);

        $observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getUser'])
            ->getMock();
        $observer->method('getUser')->willReturn($user);

        $this->loginRepository->expects($this->once())
            ->method('setUser')
            ->with($user)
            ->willReturn($this->loginRepository);

        $this->loginRepository->expects($this->once())
            ->method('addSuccessLog');

        $this->subject->execute($observer);
    }
}
