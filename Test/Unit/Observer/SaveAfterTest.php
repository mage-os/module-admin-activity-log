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
use MageOS\AdminActivityLog\Observer\SaveAfter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveAfterTest extends TestCase
{
    private ActivityConfigInterface&MockObject $activityConfig;
    private LoggerInterface&MockObject $logger;
    private Processor&MockObject $processor;
    private SaveAfter $subject;

    protected function setUp(): void
    {
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->activityConfig->method('isEnabled')->willReturn(true);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = $this->createMock(Processor::class);

        $this->subject = new SaveAfter(
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

    /**
     * Create a mock that supports getCheckIfIsNew, getData (array iteration), and setOrigData
     */
    private function createObjectMockForNew(array $data = []): DataObject&MockObject
    {
        $origData = [];

        $object = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getCheckIfIsNew', 'setOrigData', 'getOrigData'])
            ->onlyMethods(['getData'])
            ->getMock();

        $object->method('getCheckIfIsNew')->willReturn(true);

        $allData = array_merge($data, ['check_if_is_new' => true]);
        $object->method('getData')
            ->willReturnCallback(function ($key = '') use ($allData) {
                if ($key === '') {
                    return $allData;
                }
                return $allData[$key] ?? null;
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

    public function testCallsModelAddAfterForNewNonSystemConfigObject(): void
    {
        $object = $this->createObjectMockForNew();

        $this->processor->method('getInitAction')->willReturn('catalog_product_save');
        $this->processor->expects($this->once())->method('modelAddAfter')->with($object);
        $this->processor->expects($this->never())->method('modelEditAfter');

        $this->subject->execute($this->createObserverWithObject($object));
    }

    public function testCallsModelEditAfterForNewSystemConfigObject(): void
    {
        $object = $this->createObjectMockForNew();

        $this->processor->method('getInitAction')->willReturn(SaveAfter::SYSTEM_CONFIG);
        $this->processor->expects($this->once())->method('modelEditAfter')->with($object);
        $this->processor->expects($this->never())->method('modelAddAfter');

        $this->subject->execute($this->createObserverWithObject($object));
    }

    public function testSyncsOrigDataForNewObjects(): void
    {
        $object = $this->createObjectMockForNew(['name' => 'Product', 'sku' => 'ABC']);

        $this->processor->method('getInitAction')->willReturn('catalog_product_save');

        $this->subject->execute($this->createObserverWithObject($object));

        $this->assertSame('Product', $object->getOrigData('name'));
        $this->assertSame('ABC', $object->getOrigData('sku'));
    }

    public function testCallsModelEditAfterForExistingValidObject(): void
    {
        $object = new DataObject(['check_if_is_new' => false]);

        $this->processor->method('validate')->willReturn(true);
        $this->processor->method('getEventConfig')->willReturn(null);
        $this->processor->expects($this->once())->method('modelEditAfter')->with($object);
        $this->processor->expects($this->never())->method('modelDeleteAfter');

        $this->subject->execute($this->createObserverWithObject($object));
    }

    public function testCallsModelDeleteAfterForMassCancelAction(): void
    {
        $object = new DataObject(['check_if_is_new' => false]);

        $this->processor->method('validate')->willReturn(true);
        $this->processor->method('getEventConfig')
            ->with('action')
            ->willReturn(SaveAfter::ACTION_MASSCANCEL);

        $this->processor->expects($this->once())->method('modelDeleteAfter')->with($object);
        $this->processor->expects($this->once())->method('modelEditAfter')->with($object);

        $this->subject->execute($this->createObserverWithObject($object));
    }

    public function testSkipsExistingObjectWhenValidationFails(): void
    {
        $object = new DataObject(['check_if_is_new' => false]);

        $this->processor->method('validate')->willReturn(false);
        $this->processor->expects($this->never())->method('modelEditAfter');
        $this->processor->expects($this->never())->method('modelDeleteAfter');
        $this->processor->expects($this->never())->method('modelAddAfter');

        $this->subject->execute($this->createObserverWithObject($object));
    }
}
