<?php

namespace MageOS\AdminActivityLog\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use MageOS\AdminActivityLog\Api\ActivityRepositoryInterface;
use MageOS\AdminActivityLog\Block\Adminhtml\ActivityLogListing;
use MageOS\AdminActivityLog\Helper\Browser;
use MageOS\AdminActivityLog\Model\Activity;
use MageOS\AdminActivityLog\Model\ResourceModel\ActivityLog\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActivityLogListingTest extends TestCase
{

    private RequestInterface&MockObject $request;
    private Context&MockObject $context;
    private ActivityRepositoryInterface&MockObject $activityRepository;
    private Browser&MockObject $browser;
    private JsonHelper&MockObject $jsonHelper;
    private DirectoryHelper&MockObject $directoryHelper;
    private ActivityLogListing $block;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->context->method('getRequest')->willReturn($this->request);

        $this->activityRepository = $this->createMock(ActivityRepositoryInterface::class);
        $this->browser = $this->createMock(Browser::class);
        $this->jsonHelper = $this->createMock(JsonHelper::class);
        $this->directoryHelper = $this->createMock(DirectoryHelper::class);

        $this->block = new ActivityLogListing(
            $this->activityRepository,
            $this->browser,
            $this->context,
            [],
            $this->jsonHelper,
            $this->directoryHelper
        );
    }

    public function testGetLogListingActivityFound(): void
    {
        $id = 1;
        $data = [
            'entity_id' => '123',
            'activity_id' => $id,
            'field_name' => 'test',
            'old_value' => '',
            'new_value' => 'add'
        ];

        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($id);

        $activityLogCollection = $this->createMock(Collection::class);
        $activityLogCollection->expects($this->once())->method('getData')->willReturn($data);
        $this->activityRepository->expects($this->once())->method('getActivityLog')->with($id)->willReturn($activityLogCollection);

        $this->assertEquals($data, $this->block->getLogListing());
    }

    public function testGetLogListingActivityNotFound(): void
    {
        $id = 1;
        $data = [];

        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($id);

        $activityLogCollection = $this->createMock(Collection::class);
        $activityLogCollection->expects($this->once())->method('getData')->willReturn($data);
        $this->activityRepository->expects($this->once())->method('getActivityLog')->with($id)->willReturn($activityLogCollection);

        $this->assertEquals($data, $this->block->getLogListing());
    }

    public function testGetLogListingActivityNullableId(): void
    {
        $id = null;
        $data = [];

        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($id);

        $activityLogCollection = $this->createMock(Collection::class);
        $activityLogCollection->expects($this->once())->method('getData')->willReturn($data);
        $this->activityRepository->expects($this->once())->method('getActivityLog')->with($id)->willReturn($activityLogCollection);

        $this->assertEquals($data, $this->block->getLogListing());
    }

    public function testGetAdminDetails(): void
    {
        $id = 1;
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0';
        $user = 'user';
        $module = 'System Configuration';
        $name = 'John Smith';
        $fullaction = 'Adminhtml/System/Config/Edit';
        $path = 'System Configuration > General > General > Single-Store Mode > Enable Single-Store Mode';
        $browser = 'Browser Name: Chrome
            Browser Version: 143.0.0.0
            Platform: Linux
            Device: Desktop';
        $date = '2025-12-14 09:49:29';

        $result = [
            'username' => $user,
            'module' => $module,
            'name' => $name,
            'fullaction' => $fullaction,
            'path' => $path,
            'browser' => $browser,
            'date' => $date
        ];

        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($id);

        $activity = $this->createMock(Activity::class);
        $activity->expects($this->once())->method('getUserAgent')->willReturn($userAgent);
        $activity->expects($this->once())->method('getUsername')->willReturn($user);
        $activity->expects($this->once())->method('getModule')->willReturn($module);
        $activity->expects($this->once())->method('getName')->willReturn($name);
        $activity->expects($this->once())->method('getFullaction')->willReturn($fullaction);
        $activity->expects($this->once())->method('getItemPath')->willReturn($path);
        $activity->expects($this->once())->method('getUpdatedAt')->willReturn($date);

        $this->activityRepository->expects($this->once())->method('getActivityById')->with($id)->willReturn($activity);

        $this->browser->expects($this->once())->method('reset');
        $this->browser->expects($this->once())->method('setUserAgent')->with($userAgent);
        $this->browser->expects($this->once())->method('__toString')->willReturn($browser);

        $this->assertEquals($result, $this->block->getAdminDetails());
    }

    public function testGetActivityRepository(): void
    {
        $this->assertInstanceOf(ActivityRepositoryInterface::class, $this->block->getActivityRepository());
    }

}