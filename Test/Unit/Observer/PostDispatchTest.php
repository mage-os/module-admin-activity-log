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
use MageOS\AdminActivityLog\Model\Processor;
use MageOS\AdminActivityLog\Observer\PostDispatch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PostDispatchTest extends TestCase
{
    private ActivityConfigInterface&MockObject $activityConfig;
    private LoggerInterface&MockObject $logger;
    private Processor&MockObject $processor;
    private PostDispatch $subject;

    protected function setUp(): void
    {
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = $this->createMock(Processor::class);

        $this->subject = new PostDispatch(
            $this->activityConfig,
            $this->logger,
            $this->processor
        );
    }

    public function testProcessCallsSaveLogs(): void
    {
        $this->activityConfig->method('isEnabled')->willReturn(true);
        $observer = $this->createMock(Observer::class);

        $this->processor->expects($this->once())->method('saveLogs');

        $this->subject->execute($observer);
    }

    public function testProcessSkipsWhenDisabled(): void
    {
        $this->activityConfig->method('isEnabled')->willReturn(false);
        $observer = $this->createMock(Observer::class);

        $this->processor->expects($this->never())->method('saveLogs');

        $this->subject->execute($observer);
    }
}
