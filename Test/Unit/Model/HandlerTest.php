<?php
/**
 * MageOS
 *
 * @category   MageOS
 * @package    MageOS_AdminActivityLog
 * @copyright  Copyright (C) 2018 Kiwi Commerce Ltd (https://kiwicommerce.co.uk/)
 * @copyright  Copyright (C) 2025 MageOS (https://mage-os.org/)
 * @license    https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace MageOS\AdminActivityLog\Test\Unit\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\Header;
use Magento\Framework\UrlInterface;
use MageOS\AdminActivityLog\Api\FieldTrackerInterface;
use MageOS\AdminActivityLog\Model\ActivityLog;
use MageOS\AdminActivityLog\Model\ActivityLogFactory;
use MageOS\AdminActivityLog\Model\Handler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    private FieldTrackerInterface&MockObject $fieldTracker;
    private Header&MockObject $header;
    private Http&MockObject $request;
    private UrlInterface&MockObject $urlInterface;
    private ActivityLogFactory&MockObject $activityLogFactory;
    private Handler $handler;

    protected function setUp(): void
    {
        $this->fieldTracker = $this->createMock(FieldTrackerInterface::class);
        $this->header = $this->createMock(Header::class);
        $this->request = $this->createMock(Http::class);
        $this->urlInterface = $this->createMock(UrlInterface::class);
        $this->activityLogFactory = $this->createMock(ActivityLogFactory::class);

        $this->handler = new Handler(
            $this->fieldTracker,
            $this->header,
            $this->request,
            $this->urlInterface,
            $this->activityLogFactory
        );
    }

    public function testInitLogReturnsEmptyArrayForEmptyInput(): void
    {
        $this->activityLogFactory->expects($this->never())->method('create');

        $result = $this->handler->initLog([]);

        $this->assertSame([], $result);
    }

    public function testInitLogWrapsDataIntoActivityLogObjects(): void
    {
        $logData = [
            'name' => ['old_value' => 'Old Name', 'new_value' => 'New Name'],
            'email' => ['old_value' => 'old@example.com', 'new_value' => 'new@example.com'],
        ];

        $logMockName = $this->createMock(ActivityLog::class);
        $logMockName->method('setData')->willReturnSelf();
        $logMockName->expects($this->once())
            ->method('setFieldName')
            ->with('name')
            ->willReturnSelf();

        $logMockEmail = $this->createMock(ActivityLog::class);
        $logMockEmail->method('setData')->willReturnSelf();
        $logMockEmail->expects($this->once())
            ->method('setFieldName')
            ->with('email')
            ->willReturnSelf();

        $this->activityLogFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($logMockName, $logMockEmail);

        $result = $this->handler->initLog($logData);

        $this->assertSame($logMockName, $result['name']);
        $this->assertSame($logMockEmail, $result['email']);
        $this->assertCount(2, $result);
    }

    public function testModelAddDelegatesToFieldTrackerAndInitLog(): void
    {
        $model = $this->createMock(DataObject::class);
        $fields = ['field1', 'field2'];
        $trackerData = [
            'status' => ['old_value' => '', 'new_value' => 'active'],
        ];

        $this->fieldTracker->expects($this->once())
            ->method('getAddData')
            ->with($model, $fields)
            ->willReturn($trackerData);

        $logMock = $this->createMock(ActivityLog::class);
        $logMock->method('setData')->willReturnSelf();
        $logMock->method('setFieldName')->willReturnSelf();

        $this->activityLogFactory->expects($this->once())
            ->method('create')
            ->willReturn($logMock);

        $result = $this->handler->modelAdd($model, $fields);

        $this->assertCount(1, $result);
        $this->assertSame($logMock, $result['status']);
    }

    public function testModelEditDelegatesToFieldTrackerAndInitLog(): void
    {
        $model = $this->createMock(DataObject::class);
        $fields = ['field1'];
        $trackerData = [
            'title' => ['old_value' => 'Old', 'new_value' => 'New'],
        ];

        $this->fieldTracker->expects($this->once())
            ->method('getEditData')
            ->with($model, $fields)
            ->willReturn($trackerData);

        $logMock = $this->createMock(ActivityLog::class);
        $logMock->method('setData')->willReturnSelf();
        $logMock->method('setFieldName')->willReturnSelf();

        $this->activityLogFactory->expects($this->once())
            ->method('create')
            ->willReturn($logMock);

        $result = $this->handler->modelEdit($model, $fields);

        $this->assertCount(1, $result);
        $this->assertSame($logMock, $result['title']);
    }

    public function testModelDeleteDelegatesToFieldTrackerAndInitLog(): void
    {
        $model = $this->createMock(DataObject::class);
        $fields = ['id'];
        $trackerData = [
            'name' => ['old_value' => 'Item', 'new_value' => ''],
        ];

        $this->fieldTracker->expects($this->once())
            ->method('getDeleteData')
            ->with($model, $fields)
            ->willReturn($trackerData);

        $logMock = $this->createMock(ActivityLog::class);
        $logMock->method('setData')->willReturnSelf();
        $logMock->method('setFieldName')->willReturnSelf();

        $this->activityLogFactory->expects($this->once())
            ->method('create')
            ->willReturn($logMock);

        $result = $this->handler->modelDelete($model, $fields);

        $this->assertCount(1, $result);
        $this->assertSame($logMock, $result['name']);
    }

    public function testModelAddReturnsEmptyArrayWhenNoFieldData(): void
    {
        $model = $this->createMock(DataObject::class);
        $fields = ['skip_field'];

        $this->fieldTracker->expects($this->once())
            ->method('getAddData')
            ->with($model, $fields)
            ->willReturn([]);

        $this->activityLogFactory->expects($this->never())->method('create');

        $result = $this->handler->modelAdd($model, $fields);

        $this->assertSame([], $result);
    }

    public function testGetRequestReturnsHttpRequest(): void
    {
        $this->assertSame($this->request, $this->handler->getRequest());
    }

    public function testGetHeaderReturnsHeaderHelper(): void
    {
        $this->assertSame($this->header, $this->handler->getHeader());
    }
}
