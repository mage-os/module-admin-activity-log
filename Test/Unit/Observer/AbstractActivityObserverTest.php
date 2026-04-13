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
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Observer\AbstractActivityObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AbstractActivityObserverTest extends TestCase
{
    private ActivityConfigInterface&MockObject $activityConfig;
    private LoggerInterface&MockObject $logger;
    private Observer&MockObject $observer;

    protected function setUp(): void
    {
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->observer = $this->createMock(Observer::class);
    }

    public function testProcessIsCalledWhenEnabled(): void
    {
        $this->activityConfig->method('isEnabled')->willReturn(true);
        $processCalled = false;

        $subject = $this->createConcreteObserver(function () use (&$processCalled) {
            $processCalled = true;
        });

        $subject->execute($this->observer);

        $this->assertTrue($processCalled);
    }

    public function testProcessIsSkippedWhenDisabled(): void
    {
        $this->activityConfig->method('isEnabled')->willReturn(false);
        $processCalled = false;

        $subject = $this->createConcreteObserver(function () use (&$processCalled) {
            $processCalled = true;
        });

        $subject->execute($this->observer);

        $this->assertFalse($processCalled);
    }

    public function testExceptionInProcessIsSwallowedAndLogged(): void
    {
        $this->activityConfig->method('isEnabled')->willReturn(true);

        $subject = $this->createConcreteObserver(function () {
            throw new \RuntimeException('Database connection lost');
        });

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Admin activity observer failed',
                $this->callback(function (array $context): bool {
                    return str_contains($context['exception'], 'Database connection lost')
                        && isset($context['trace'])
                        && isset($context['observer']);
                })
            );

        // Must not throw
        $subject->execute($this->observer);
    }

    public function testErrorInProcessIsSwallowedAndLogged(): void
    {
        $this->activityConfig->method('isEnabled')->willReturn(true);

        $subject = $this->createConcreteObserver(function () {
            throw new \Error('Call to undefined method');
        });

        $this->logger->expects($this->once())
            ->method('error');

        // \Error (not \Exception) must also be caught
        $subject->execute($this->observer);
    }

    public function testTypeErrorInProcessIsSwallowedAndLogged(): void
    {
        $this->activityConfig->method('isEnabled')->willReturn(true);

        $subject = $this->createConcreteObserver(function () {
            throw new \TypeError('Argument must be of type int, null given');
        });

        $this->logger->expects($this->once())
            ->method('error');

        $subject->execute($this->observer);
    }

    public function testLogContextIncludesConcreteObserverClassName(): void
    {
        $this->activityConfig->method('isEnabled')->willReturn(true);

        $subject = $this->createConcreteObserver(function () {
            throw new \RuntimeException('fail');
        });

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->anything(),
                $this->callback(function (array $context) use ($subject): bool {
                    return $context['observer'] === $subject::class;
                })
            );

        $subject->execute($this->observer);
    }

    public function testNoErrorLoggedOnSuccess(): void
    {
        $this->activityConfig->method('isEnabled')->willReturn(true);

        $subject = $this->createConcreteObserver(function () {
            // no-op, success
        });

        $this->logger->expects($this->never())
            ->method('error');

        $subject->execute($this->observer);
    }

    private function createConcreteObserver(\Closure $processCallback): AbstractActivityObserver
    {
        return new class ($this->activityConfig, $this->logger, $processCallback) extends AbstractActivityObserver {
            public function __construct(
                ActivityConfigInterface $activityConfig,
                LoggerInterface $logger,
                private readonly \Closure $processCallback
            ) {
                parent::__construct($activityConfig, $logger);
            }

            protected function process(Observer $observer): void
            {
                ($this->processCallback)($observer);
            }
        };
    }
}
