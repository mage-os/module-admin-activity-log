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

namespace MageOS\AdminActivityLog\Test\Unit\Plugin\App;

use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\Request\Http as HttpRequest;
use MageOS\AdminActivityLog\Model\Processor;
use MageOS\AdminActivityLog\Plugin\App\ActionPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ActionPluginTest extends TestCase
{
    private Processor&MockObject $processor;
    private LoggerInterface&MockObject $logger;
    private AbstractAction&MockObject $subject;
    private HttpRequest&MockObject $request;
    private ActionPlugin $plugin;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(Processor::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(HttpRequest::class);
        $this->subject = $this->createMock(AbstractAction::class);
        $this->subject->method('getRequest')->willReturn($this->request);

        $this->plugin = new ActionPlugin($this->processor, $this->logger);
    }

    public function testBeforeDispatchCallsProcessorInit(): void
    {
        $this->request->method('getActionName')->willReturn('edit');
        $this->request->method('getFullActionName')->willReturn('catalog_product_edit');
        $this->request->method('getModuleName')->willReturn('catalog');

        $this->processor->expects($this->once())
            ->method('init')
            ->with('catalog_product_edit', 'edit');

        $this->processor->expects($this->once())
            ->method('addPageVisitLog')
            ->with('catalog');

        $this->plugin->beforeDispatch($this->subject);
    }

    public function testBeforeDispatchSwallowsProcessorInitException(): void
    {
        $this->request->method('getActionName')->willReturn('edit');
        $this->request->method('getFullActionName')->willReturn('catalog_product_edit');

        $this->processor->method('init')
            ->willThrowException(new \RuntimeException('Config XML parse error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Admin activity plugin failed',
                $this->callback(function (array $context): bool {
                    return str_contains($context['exception'], 'Config XML parse error');
                })
            );

        // Must not throw
        $this->plugin->beforeDispatch($this->subject);
    }

    public function testBeforeDispatchSwallowsPageVisitLogException(): void
    {
        $this->request->method('getActionName')->willReturn('edit');
        $this->request->method('getFullActionName')->willReturn('catalog_product_edit');
        $this->request->method('getModuleName')->willReturn('catalog');

        $this->processor->method('init')->willReturn($this->processor);
        $this->processor->method('addPageVisitLog')
            ->willThrowException(new \RuntimeException('DB write failed'));

        $this->logger->expects($this->once())
            ->method('error');

        $this->plugin->beforeDispatch($this->subject);
    }

    public function testBeforeDispatchSwallowsThrowableErrors(): void
    {
        $this->request->method('getActionName')->willReturn('edit');
        $this->request->method('getFullActionName')->willReturn('catalog_product_edit');

        $this->processor->method('init')
            ->willThrowException(new \Error('Class not found'));

        $this->logger->expects($this->once())
            ->method('error');

        $this->plugin->beforeDispatch($this->subject);
    }

    public function testNoErrorLoggedOnSuccess(): void
    {
        $this->request->method('getActionName')->willReturn('edit');
        $this->request->method('getFullActionName')->willReturn('catalog_product_edit');
        $this->request->method('getModuleName')->willReturn('catalog');

        $this->processor->method('init')->willReturn($this->processor);

        $this->logger->expects($this->never())
            ->method('error');

        $this->plugin->beforeDispatch($this->subject);
    }
}
