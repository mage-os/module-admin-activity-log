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

namespace MageOS\AdminActivityLog\Test\Unit\Model\Handler;

use Magento\Backend\Model\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Model\Handler\PostDispatch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PostDispatchTest extends TestCase
{
    private RequestInterface&MockObject $request;
    private PostDispatch $postDispatch;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $productRepository = $this->createMock(ProductRepositoryInterface::class);
        $session = $this->createMock(Session::class);

        $this->postDispatch = new PostDispatch(
            $this->request,
            $response,
            $productRepository,
            $session
        );
    }

    // --- getProductAttributes: happy path ---

    public function testGetProductAttributesLogsGenuineStatusChange(): void
    {
        $model = new DataObject(['status' => '1']);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', '2'],
            ['attributes', [], []],
            ['inventory', [], []],
            ['remove_website', [], []],
            ['add_website', [], []],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertArrayHasKey('status', $result);
        $this->assertSame('1', $result['status']['old_value']);
        $this->assertSame('2', $result['status']['new_value']);
    }

    public function testGetProductAttributesSkipsUnchangedStatus(): void
    {
        $model = new DataObject(['status' => '1']);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', '1'],
            ['attributes', [], []],
            ['inventory', [], []],
            ['remove_website', [], []],
            ['add_website', [], []],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertArrayNotHasKey('status', $result, 'Unchanged status should not be logged');
    }

    public function testGetProductAttributesSkipsUnchangedAttribute(): void
    {
        $model = new DataObject(['name' => 'Test Product', 'price' => '99.99']);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', ''],
            ['attributes', [], ['name' => 'Test Product', 'price' => '99.99']],
            ['inventory', [], []],
            ['remove_website', [], []],
            ['add_website', [], []],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertArrayNotHasKey('name', $result, 'Unchanged attribute should not be logged');
        $this->assertArrayNotHasKey('price', $result, 'Unchanged attribute should not be logged');
    }

    public function testGetProductAttributesLogsChangedAttribute(): void
    {
        $model = new DataObject(['name' => 'Old Name']);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', ''],
            ['attributes', [], ['name' => 'New Name']],
            ['inventory', [], []],
            ['remove_website', [], []],
            ['add_website', [], []],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertArrayHasKey('name', $result);
        $this->assertSame('Old Name', $result['name']['old_value']);
        $this->assertSame('New Name', $result['name']['new_value']);
    }

    public function testGetProductAttributesSkipsUnchangedInventory(): void
    {
        $model = new DataObject(['qty' => '100']);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', ''],
            ['attributes', [], []],
            ['inventory', [], ['qty' => '100']],
            ['remove_website', [], []],
            ['add_website', [], []],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertArrayNotHasKey('qty', $result, 'Unchanged inventory should not be logged');
    }

    public function testGetProductAttributesLogsChangedInventory(): void
    {
        $model = new DataObject(['qty' => '100']);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', ''],
            ['attributes', [], []],
            ['inventory', [], ['qty' => '50']],
            ['remove_website', [], []],
            ['add_website', [], []],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertArrayHasKey('qty', $result);
        $this->assertSame('100', $result['qty']['old_value']);
        $this->assertSame('50', $result['qty']['new_value']);
    }

    // --- edge/boundary cases ---

    public function testGetProductAttributesSkipsIntVsStringEquivalent(): void
    {
        $model = new DataObject(['status' => 1]);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', '1'],
            ['attributes', [], []],
            ['inventory', [], []],
            ['remove_website', [], []],
            ['add_website', [], []],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertArrayNotHasKey('status', $result, 'int 1 vs string "1" should not be a change');
    }

    public function testGetProductAttributesSkipsNullVsEmptyString(): void
    {
        $model = new DataObject(['special_price' => null]);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', ''],
            ['attributes', [], ['special_price' => '']],
            ['inventory', [], []],
            ['remove_website', [], []],
            ['add_website', [], []],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertArrayNotHasKey('special_price', $result, 'null vs empty string should not be a change');
    }

    public function testGetProductAttributesReturnsEmptyWhenNoParams(): void
    {
        $model = new DataObject([]);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', ''],
            ['attributes', [], []],
            ['inventory', [], []],
            ['remove_website', [], []],
            ['add_website', [], []],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertEmpty($result);
    }

    public function testGetProductAttributesLogsWebsiteChanges(): void
    {
        $model = new DataObject([]);

        $this->request->method('getParam')->willReturnMap([
            ['status', '', ''],
            ['attributes', [], []],
            ['inventory', [], []],
            ['remove_website', [], [1, 2]],
            ['add_website', [], [3]],
        ]);

        $result = $this->postDispatch->getProductAttributes($model);

        $this->assertArrayHasKey('remove_website_ids', $result);
        $this->assertSame('1, 2', $result['remove_website_ids']['new_value']);
        $this->assertArrayHasKey('add_website_ids', $result);
        $this->assertSame('3', $result['add_website_ids']['new_value']);
    }
}
