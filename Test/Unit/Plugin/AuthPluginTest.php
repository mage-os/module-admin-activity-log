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

namespace MageOS\AdminActivityLog\Test\Unit\Plugin;

use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Auth\Session;
use Magento\User\Model\User;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Api\LoginRepositoryInterface;
use MageOS\AdminActivityLog\Plugin\AuthPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AuthPluginTest extends TestCase
{
    private ActivityConfigInterface&MockObject $activityConfig;
    private LoginRepositoryInterface&MockObject $loginRepository;
    private LoggerInterface&MockObject $logger;
    private Auth&MockObject $auth;
    private AuthPlugin $plugin;

    protected function setUp(): void
    {
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->loginRepository = $this->createMock(LoginRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->auth = $this->createMock(Auth::class);

        $this->plugin = new AuthPlugin(
            $this->activityConfig,
            $this->loginRepository,
            $this->logger
        );
    }

    public function testBeforeLogoutLogsWhenEnabled(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(true);

        $user = $this->createMock(User::class);
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['getUser'])
            ->getMock();
        $session->method('getUser')->willReturn($user);
        $this->auth->method('getAuthStorage')->willReturn($session);

        $this->loginRepository->expects($this->once())
            ->method('setUser')
            ->with($user)
            ->willReturn($this->loginRepository);

        $this->loginRepository->expects($this->once())
            ->method('addLogoutLog');

        $this->plugin->beforeLogout($this->auth);
    }

    public function testBeforeLogoutSkipsWhenDisabled(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(false);

        $this->loginRepository->expects($this->never())
            ->method('setUser');

        $this->plugin->beforeLogout($this->auth);
    }

    public function testBeforeLogoutSwallowsExceptionFromAuthStorage(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(true);

        $this->auth->method('getAuthStorage')
            ->willThrowException(new \RuntimeException('Session expired'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Admin activity logout logging failed',
                $this->callback(function (array $context): bool {
                    return str_contains($context['exception'], 'Session expired');
                })
            );

        // Must not throw — logout must proceed
        $this->plugin->beforeLogout($this->auth);
    }

    public function testBeforeLogoutSwallowsExceptionFromAddLogoutLog(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(true);

        $user = $this->createMock(User::class);
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['getUser'])
            ->getMock();
        $session->method('getUser')->willReturn($user);
        $this->auth->method('getAuthStorage')->willReturn($session);

        $this->loginRepository->method('setUser')->willReturn($this->loginRepository);
        $this->loginRepository->method('addLogoutLog')
            ->willThrowException(new \RuntimeException('DB write failed'));

        $this->logger->expects($this->once())
            ->method('error');

        $this->plugin->beforeLogout($this->auth);
    }

    public function testNoErrorLoggedOnSuccess(): void
    {
        $this->activityConfig->method('isLoginEnabled')->willReturn(true);

        $user = $this->createMock(User::class);
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['getUser'])
            ->getMock();
        $session->method('getUser')->willReturn($user);
        $this->auth->method('getAuthStorage')->willReturn($session);

        $this->loginRepository->method('setUser')->willReturn($this->loginRepository);

        $this->logger->expects($this->never())
            ->method('error');

        $this->plugin->beforeLogout($this->auth);
    }
}
