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

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Model\Activity\SystemConfig;
use MageOS\AdminActivityLog\Model\Config;
use MageOS\AdminActivityLog\Model\Handler;
use MageOS\AdminActivityLog\Model\Handler\PostDispatch;
use MageOS\AdminActivityLog\Model\Processor;
use MageOS\AdminActivityLog\Model\Processor\ActivityContext;
use MageOS\AdminActivityLog\Model\Processor\RequestContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    private Config&MockObject $config;
    private Session&MockObject $authSession;
    private Handler&MockObject $handler;
    private StoreManagerInterface&MockObject $storeManager;
    private ActivityConfigInterface&MockObject $activityConfig;
    private PostDispatch&MockObject $postDispatch;
    private SystemConfig&MockObject $systemConfig;
    private RequestContext&MockObject $requestContext;
    private ActivityContext&MockObject $activityContext;
    private RequestInterface&MockObject $request;
    private RemoteAddress&MockObject $remoteAddress;
    private Processor $processor;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->authSession = $this->createMock(Session::class);
        $this->handler = $this->createMock(Handler::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->postDispatch = $this->createMock(PostDispatch::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);

        // Create mocks for RequestContext dependencies
        $this->request = $this->createMock(RequestInterface::class);
        $this->remoteAddress = $this->createMock(RemoteAddress::class);

        // Create RequestContext mock
        $this->requestContext = $this->createMock(RequestContext::class);
        $this->requestContext->method('getRequest')->willReturn($this->request);
        $this->requestContext->method('getRemoteAddress')->willReturn($this->remoteAddress);

        // Create ActivityContext mock
        $this->activityContext = $this->createMock(ActivityContext::class);

        $this->processor = new Processor(
            $this->config,
            $this->authSession,
            $this->handler,
            $this->storeManager,
            $this->activityConfig,
            $this->postDispatch,
            $this->systemConfig,
            $this->requestContext,
            $this->activityContext
        );
    }

    /**
     * @dataProvider escapeStringDataProvider
     */
    public function testEscapeString(string $input, string $delimiter, string $expected): void
    {
        $result = $this->processor->escapeString($input, $delimiter);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{input: string, delimiter: string, expected: string}>
     */
    public static function escapeStringDataProvider(): array
    {
        return [
            'underscore with space delimiter' => [
                'input' => 'catalog_product_view',
                'delimiter' => ' ',
                'expected' => 'Catalog Product View'
            ],
            'underscore with slash delimiter' => [
                'input' => 'catalog_product_view',
                'delimiter' => '/',
                'expected' => 'Catalog/Product/View'
            ],
            'single word' => [
                'input' => 'products',
                'delimiter' => ' ',
                'expected' => 'Products'
            ],
            'uppercase input' => [
                'input' => 'CATALOG_PRODUCT',
                'delimiter' => ' ',
                'expected' => 'Catalog Product'
            ],
            'mixed case input' => [
                'input' => 'CaTaLoG_ProDuCt',
                'delimiter' => ' ',
                'expected' => 'Catalog Product'
            ],
            'empty string' => [
                'input' => '',
                'delimiter' => ' ',
                'expected' => ''
            ],
            'multiple underscores' => [
                'input' => 'admin__activity__log',
                'delimiter' => ' ',
                'expected' => 'Admin Activity Log'
            ],
            'trailing underscore' => [
                'input' => 'catalog_product_',
                'delimiter' => ' ',
                'expected' => 'Catalog Product'
            ],
            'leading underscore' => [
                'input' => '_catalog_product',
                'delimiter' => ' ',
                'expected' => 'Catalog Product'
            ],
        ];
    }

    /**
     * @dataProvider isValidActionDataProvider
     */
    public function testIsValidAction(string $module, string $fullAction, bool $expected): void
    {
        $result = $this->processor->isValidAction($module, $fullAction);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{module: string, fullAction: string, expected: bool}>
     */
    public static function isValidActionDataProvider(): array
    {
        return [
            'valid action' => [
                'module' => 'catalog',
                'fullAction' => 'catalog_product_edit',
                'expected' => true
            ],
            'skip action - mui_index_render' => [
                'module' => 'mui',
                'fullAction' => 'mui_index_render',
                'expected' => false
            ],
            'skip action - adminactivity_activity_index' => [
                'module' => 'adminactivity',
                'fullAction' => 'adminactivity_activity_index',
                'expected' => false
            ],
            'skip action - adminactivity_activity_log' => [
                'module' => 'adminactivity',
                'fullAction' => 'adminactivity_activity_log',
                'expected' => false
            ],
            'skip module - mui' => [
                'module' => 'mui',
                'fullAction' => 'some_valid_action',
                'expected' => false
            ],
            'skip module - MUI uppercase' => [
                'module' => 'MUI',
                'fullAction' => 'some_valid_action',
                'expected' => false
            ],
            'skip action case insensitive' => [
                'module' => 'adminactivity',
                'fullAction' => 'ADMINACTIVITY_ACTIVITY_INDEX',
                'expected' => false
            ],
            'valid customer action' => [
                'module' => 'customer',
                'fullAction' => 'customer_index_edit',
                'expected' => true
            ],
            'valid sales action' => [
                'module' => 'sales',
                'fullAction' => 'sales_order_view',
                'expected' => true
            ],
        ];
    }

    public function testGetScopeReturnsStoresWhenStoreParamIsNumeric(): void
    {
        $this->request
            ->method('getParam')
            ->willReturnMap([
                ['store', null, 5],
                ['website', null, null],
                ['scope', null, null]
            ]);

        $result = $this->processor->getScope();

        $this->assertSame('stores', $result);
    }

    public function testGetScopeReturnsStoresWhenStoreParamIsStringCode(): void
    {
        $this->request
            ->method('getParam')
            ->willReturnMap([
                ['store', null, 'default'],
                ['website', null, null],
                ['scope', null, null]
            ]);

        $result = $this->processor->getScope();

        $this->assertSame('stores', $result);
    }

    public function testGetScopeReturnsStoresWhenScopeIsStores(): void
    {
        $this->request
            ->method('getParam')
            ->willReturnMap([
                ['store', null, null],
                ['website', null, null],
                ['scope', null, 'stores']
            ]);

        $result = $this->processor->getScope();

        $this->assertSame('stores', $result);
    }

    public function testGetScopeReturnsWebsiteWhenWebsiteParamIsNumeric(): void
    {
        $this->request
            ->method('getParam')
            ->willReturnMap([
                ['store', null, null],
                ['website', null, 2],
                ['scope', null, null]
            ]);

        $result = $this->processor->getScope();

        $this->assertSame('website', $result);
    }

    public function testGetScopeReturnsWebsiteWhenScopeIsWebsites(): void
    {
        $this->request
            ->method('getParam')
            ->willReturnMap([
                ['store', null, null],
                ['website', null, null],
                ['scope', null, 'websites']
            ]);

        $result = $this->processor->getScope();

        $this->assertSame('website', $result);
    }

    public function testGetScopeReturnsDefaultWhenNoParams(): void
    {
        $this->request
            ->method('getParam')
            ->willReturnMap([
                ['store', null, null],
                ['website', null, null],
                ['scope', null, null]
            ]);

        $result = $this->processor->getScope();

        $this->assertSame('default', $result);
    }

    public function testGetConfigReturnsConfigInstance(): void
    {
        $result = $this->processor->getConfig();

        $this->assertSame($this->config, $result);
    }

    public function testGetHandlerReturnsHandlerInstance(): void
    {
        $result = $this->processor->getHandler();

        $this->assertSame($this->handler, $result);
    }

    public function testGetRequestReturnsRequestInstance(): void
    {
        $result = $this->processor->getRequest();

        $this->assertSame($this->request, $result);
    }

    public function testGetRemoteAddressReturnsRemoteAddressInstance(): void
    {
        $result = $this->processor->getRemoteAddress();

        $this->assertSame($this->remoteAddress, $result);
    }

    public function testGetInitActionReturnsEmptyStringInitially(): void
    {
        $result = $this->processor->getInitAction();

        $this->assertSame('', $result);
    }

    public function testInitSetsActionNameAndEventConfig(): void
    {
        $fullActionName = 'catalog_product_edit';
        $actionName = 'edit';
        $eventConfig = ['module' => 'catalog_product', 'action' => 'edit'];

        $this->config
            ->expects($this->once())
            ->method('getEventByAction')
            ->with($fullActionName)
            ->willReturn($eventConfig);

        $result = $this->processor->init($fullActionName, $actionName);

        $this->assertSame($this->processor, $result);
        $this->assertSame($fullActionName, $this->processor->getInitAction());
    }

    public function testGetEventConfigReturnsNullInitially(): void
    {
        $result = $this->processor->getEventConfig();

        $this->assertNull($result);
    }

    public function testGetEventConfigWithTypeReturnsSpecificValue(): void
    {
        $fullActionName = 'catalog_product_edit';
        $actionName = 'edit';
        $eventConfig = ['module' => 'catalog_product', 'action' => 'edit'];

        $this->config
            ->method('getEventByAction')
            ->with($fullActionName)
            ->willReturn($eventConfig);

        $this->processor->init($fullActionName, $actionName);

        $this->assertSame('catalog_product', $this->processor->getEventConfig('module'));
        $this->assertSame('edit', $this->processor->getEventConfig('action'));
        $this->assertNull($this->processor->getEventConfig('nonexistent'));
    }

    /**
     * @dataProvider sanitizeForwardedIpDataProvider
     */
    public function testSanitizeForwardedIp(?string $input, ?string $expected): void
    {
        $result = $this->processor->sanitizeForwardedIp($input);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{input: ?string, expected: ?string}>
     */
    public static function sanitizeForwardedIpDataProvider(): array
    {
        return [
            'null input' => [
                'input' => null,
                'expected' => null
            ],
            'empty string' => [
                'input' => '',
                'expected' => null
            ],
            'valid IPv4' => [
                'input' => '192.168.1.1',
                'expected' => '192.168.1.1'
            ],
            'valid IPv6' => [
                'input' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
                'expected' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'
            ],
            'multiple valid IPs' => [
                'input' => '192.168.1.1, 10.0.0.1, 172.16.0.1',
                'expected' => '192.168.1.1, 10.0.0.1, 172.16.0.1'
            ],
            'invalid IP' => [
                'input' => 'not-an-ip',
                'expected' => null
            ],
            'mixed valid and invalid IPs' => [
                'input' => '192.168.1.1, invalid, 10.0.0.1',
                'expected' => '192.168.1.1, 10.0.0.1'
            ],
            'all invalid IPs' => [
                'input' => 'invalid1, invalid2, not-ip',
                'expected' => null
            ],
            'IP with extra spaces' => [
                'input' => '  192.168.1.1  ,  10.0.0.1  ',
                'expected' => '192.168.1.1, 10.0.0.1'
            ],
            'XSS attempt' => [
                'input' => '<script>alert("xss")</script>',
                'expected' => null
            ],
            'SQL injection attempt' => [
                'input' => "192.168.1.1'; DROP TABLE users; --",
                'expected' => null
            ],
            'partial valid IP' => [
                'input' => '192.168.1',
                'expected' => null
            ],
            'IP with port' => [
                'input' => '192.168.1.1:8080',
                'expected' => null
            ],
            'localhost IPv4' => [
                'input' => '127.0.0.1',
                'expected' => '127.0.0.1'
            ],
            'localhost IPv6' => [
                'input' => '::1',
                'expected' => '::1'
            ],
        ];
    }

    public function testGetStoreIdFallsBackToStoreManagerWhenStoreIdIsEmptyArray(): void
    {
        $model = new DataObject(['store_id' => []]);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(7);

        $this->storeManager
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $result = $this->processor->getStoreId($model);

        $this->assertSame(7, $result);
    }
}
