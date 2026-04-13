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

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Model\Processor;
use MageOS\AdminActivityLog\Observer\DeleteAfter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeleteAfterTest extends TestCase
{
    private ActivityConfigInterface&MockObject $activityConfig;
    private LoggerInterface&MockObject $logger;
    private Processor&MockObject $processor;
    private DeleteAfter $subject;

    protected function setUp(): void
    {
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->activityConfig->method('isEnabled')->willReturn(true);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = $this->createMock(Processor::class);

        $this->subject = new DeleteAfter(
            $this->activityConfig,
            $this->logger,
            $this->processor
        );
    }

    private function createObserverWithObject(DataObject $object): Observer
    {
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getObject'])
            ->getMock();
        $event->method('getObject')->willReturn($object);

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        return $observer;
    }

    public function testAlwaysCallsModelDeleteAfter(): void
    {
        $object = new DataObject();

        $this->processor->method('validate')->willReturn(false);
        $this->processor->expects($this->once())->method('modelDeleteAfter')->with($object);

        $this->subject->execute($this->createObserverWithObject($object));
    }

    public function testCallsModelEditAfterForSystemConfigDelete(): void
    {
        $object = new DataObject();

        $this->processor->method('validate')->willReturn(true);
        $this->processor->method('getInitAction')->willReturn(DeleteAfter::SYSTEM_CONFIG);

        $this->processor->expects($this->once())->method('modelEditAfter')->with($object);
        $this->processor->expects($this->once())->method('modelDeleteAfter')->with($object);

        $this->subject->execute($this->createObserverWithObject($object));
    }

    public function testSkipsModelEditAfterWhenNotSystemConfig(): void
    {
        $object = new DataObject();

        $this->processor->method('validate')->willReturn(true);
        $this->processor->method('getInitAction')->willReturn('catalog_product_delete');

        $this->processor->expects($this->never())->method('modelEditAfter');
        $this->processor->expects($this->once())->method('modelDeleteAfter')->with($object);

        $this->subject->execute($this->createObserverWithObject($object));
    }

    public function testSkipsModelEditAfterWhenValidationFails(): void
    {
        $object = new DataObject();

        $this->processor->method('validate')->willReturn(false);
        $this->processor->method('getInitAction')->willReturn(DeleteAfter::SYSTEM_CONFIG);

        $this->processor->expects($this->never())->method('modelEditAfter');
        $this->processor->expects($this->once())->method('modelDeleteAfter')->with($object);

        $this->subject->execute($this->createObserverWithObject($object));
    }
}
