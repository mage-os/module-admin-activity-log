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

use InvalidArgumentException;
use Magento\Framework\Model\AbstractModel;
use MageOS\AdminActivityLog\Model\Activity;
use MageOS\AdminActivityLog\Model\ModelResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModelResolverTest extends TestCase
{
    private Activity&MockObject $mockModel;
    private object $mockFactory;
    private ModelResolver $modelResolver;
    private ModelResolver $modelResolverWithAllowlist;

    protected function setUp(): void
    {
        $this->mockModel = $this->createMock(Activity::class);
        $mockModel = $this->mockModel;

        $this->mockFactory = new class ($mockModel) {
            public function __construct(private readonly Activity $model) {}
            public function create(): Activity { return $this->model; }
        };

        $this->modelResolver = new ModelResolver();
        $this->modelResolverWithAllowlist = new ModelResolver(
            [Activity::class => $this->mockFactory]
        );
    }

    public function testGetModelWithValidClass(): void
    {
        $result = $this->modelResolverWithAllowlist->getModel(Activity::class);

        $this->assertSame($this->mockModel, $result);
    }

    public function testGetModelWithDisallowedClassThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Class "%s" is not in the allowed model classes list', Activity::class)
        );

        $this->modelResolver->getModel(Activity::class);
    }

    public function testGetModelWithNonExistentClassThrowsException(): void
    {
        $className = 'NonExistentClass\\That\\Does\\Not\\Exist';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Class "%s" is not in the allowed model classes list', $className)
        );

        $this->modelResolver->getModel($className);
    }

    public function testGetModelStripsInterceptorSuffix(): void
    {
        $interceptorClass = Activity::class . '\\Interceptor';

        $result = $this->modelResolverWithAllowlist->getModel($interceptorClass);

        $this->assertSame($this->mockModel, $result);
    }

    public function testLoadModelWithoutField(): void
    {
        $entityId = 123;

        $this->mockModel
            ->expects($this->once())
            ->method('load')
            ->with($entityId)
            ->willReturnSelf();

        $result = $this->modelResolverWithAllowlist->loadModel(Activity::class, $entityId);

        $this->assertSame($this->mockModel, $result);
    }

    public function testLoadModelWithField(): void
    {
        $entityId = 'test-sku';
        $field = 'sku';

        $this->mockModel
            ->expects($this->once())
            ->method('load')
            ->with($entityId, $field)
            ->willReturnSelf();

        $result = $this->modelResolverWithAllowlist->loadModel(Activity::class, $entityId, $field);

        $this->assertSame($this->mockModel, $result);
    }

    public function testLoadModelWithIntegerId(): void
    {
        $entityId = 42;

        $this->mockModel
            ->expects($this->once())
            ->method('load')
            ->with($entityId)
            ->willReturnSelf();

        $result = $this->modelResolverWithAllowlist->loadModel(Activity::class, $entityId);

        $this->assertSame($this->mockModel, $result);
    }

    public function testIsValidModelClassReturnsTrueForAllowedClass(): void
    {
        $this->assertTrue($this->modelResolverWithAllowlist->isValidModelClass(Activity::class));
    }

    public function testIsValidModelClassReturnsFalseForDisallowedClass(): void
    {
        $this->assertFalse($this->modelResolver->isValidModelClass(Activity::class));
    }

    public function testIsAllowedModelClassReturnsFalseWhenNoAllowlistConfigured(): void
    {
        $this->assertFalse($this->modelResolver->isAllowedModelClass(Activity::class));
    }

    public function testIsAllowedModelClassReturnsTrueForConfiguredClass(): void
    {
        $this->assertTrue($this->modelResolverWithAllowlist->isAllowedModelClass(Activity::class));
    }

    public function testIsAllowedModelClassReturnsFalseForUnconfiguredClass(): void
    {
        $restrictedResolver = new ModelResolver(['Some\\Other\\Model' => $this->mockFactory]);

        $this->assertFalse($restrictedResolver->isAllowedModelClass(Activity::class));
    }

    public function testIsAllowedModelClassStripsInterceptorSuffix(): void
    {
        $interceptorClass = Activity::class . '\\Interceptor';

        $this->assertTrue($this->modelResolverWithAllowlist->isAllowedModelClass($interceptorClass));
    }

    public function testSubclassIsNotImplicitlyAllowed(): void
    {
        // Unlike the previous string-based allowlist, inheritance does NOT grant access.
        // Only the exact class registered in the factory map is allowed.
        $subclassResolver = new ModelResolver(
            [AbstractModel::class => $this->mockFactory]
        );

        $this->assertFalse($subclassResolver->isAllowedModelClass(Activity::class));
    }
}
