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
use MageOS\AdminActivityLog\Api\ActivityRepositoryInterface;
use MageOS\AdminActivityLog\Model\Processor;
use MageOS\AdminActivityLog\Observer\SaveBefore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveBeforeTest extends TestCase
{
    private ActivityConfigInterface&MockObject $activityConfig;
    private LoggerInterface&MockObject $logger;
    private Processor&MockObject $processor;
    private ActivityRepositoryInterface&MockObject $activityRepository;
    private SaveBefore $subject;

    protected function setUp(): void
    {
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->activityConfig->method('isEnabled')->willReturn(true);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = $this->createMock(Processor::class);
        $this->activityRepository = $this->createMock(ActivityRepositoryInterface::class);

        $this->subject = new SaveBefore(
            $this->activityConfig,
            $this->logger,
            $this->processor,
            $this->activityRepository
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

    /**
     * Create a mock that supports getId, setCheckIfIsNew, getCheckIfIsNew, and setOrigData/getOrigData
     *
     * @param mixed $id
     * @param array<string, mixed> $data
     * @return DataObject&MockObject
     */
    private function createObjectMock(mixed $id = null, array $data = []): DataObject&MockObject
    {
        $checkIfIsNew = null;
        $origData = [];

        $object = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getId', 'setCheckIfIsNew', 'getCheckIfIsNew', 'setOrigData', 'getOrigData'])
            ->getMock();

        $object->method('getId')->willReturn($id);

        $object->method('setCheckIfIsNew')
            ->willReturnCallback(function ($value) use ($object, &$checkIfIsNew) {
                $checkIfIsNew = $value;
                return $object;
            });

        $object->method('getCheckIfIsNew')
            ->willReturnCallback(function () use (&$checkIfIsNew) {
                return $checkIfIsNew;
            });

        $object->method('setOrigData')
            ->willReturnCallback(function ($key, $value) use ($object, &$origData) {
                $origData[$key] = $value;
                return $object;
            });

        $object->method('getOrigData')
            ->willReturnCallback(function ($key = null) use (&$origData) {
                if ($key === null) {
                    return $origData;
                }
                return $origData[$key] ?? null;
            });

        return $object;
    }

    public function testSetsCheckIfIsNewTrueWhenIdIsZero(): void
    {
        $object = $this->createObjectMock(0);
        $this->processor->method('validate')->willReturn(false);

        $this->subject->execute($this->createObserverWithObject($object));

        $this->assertTrue($object->getCheckIfIsNew());
    }

    public function testSetsCheckIfIsNewFalseWhenIdIsNonZero(): void
    {
        $object = $this->createObjectMock(42);
        $this->processor->method('validate')->willReturn(false);

        $this->subject->execute($this->createObserverWithObject($object));

        $this->assertFalse($object->getCheckIfIsNew());
    }

    public function testLoadsOrigDataWhenProcessorValidates(): void
    {
        $object = $this->createObjectMock(10);
        $oldData = new DataObject(['name' => 'Old Name', 'status' => 'active']);

        $this->processor->method('validate')->willReturn(true);
        $this->activityRepository->method('getOldData')->willReturn($oldData);

        $this->subject->execute($this->createObserverWithObject($object));

        $this->assertSame('Old Name', $object->getOrigData('name'));
        $this->assertSame('active', $object->getOrigData('status'));
    }

    public function testSkipsOrigDataWhenProcessorDoesNotValidate(): void
    {
        $object = $this->createObjectMock(10);
        $this->processor->method('validate')->willReturn(false);

        $this->activityRepository->expects($this->never())
            ->method('getOldData');

        $this->subject->execute($this->createObserverWithObject($object));
    }

    public function testReturnsEarlyWhenGetOldDataReturnsFalse(): void
    {
        $object = $this->createObjectMock(10);
        $this->processor->method('validate')->willReturn(true);
        $this->activityRepository->method('getOldData')->willReturn(false);

        $object->expects($this->never())->method('setOrigData');

        $this->subject->execute($this->createObserverWithObject($object));
    }

    public function testSetsCheckIfIsNewTrueWhenIdIsNull(): void
    {
        $object = $this->createObjectMock(null);
        $this->processor->method('validate')->willReturn(false);

        $this->subject->execute($this->createObserverWithObject($object));

        $this->assertTrue($object->getCheckIfIsNew());
    }
}
