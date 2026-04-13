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

namespace MageOS\AdminActivityLog\Test\Unit\Model\Activity;

use Magento\Framework\DataObject;
use MageOS\AdminActivityLog\Api\ActivityConfigInterface;
use MageOS\AdminActivityLog\Api\FieldCheckerInterface;
use MageOS\AdminActivityLog\Model\Activity\FieldTracker;
use MageOS\AdminActivityLog\Model\Activity\SystemConfig;
use MageOS\AdminActivityLog\Model\Activity\ThemeConfig;
use MageOS\AdminActivityLog\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class FieldTrackerTest extends TestCase
{
    private SystemConfig&MockObject $systemConfig;
    private ThemeConfig&MockObject $themeConfig;
    private Config&MockObject $config;
    private ActivityConfigInterface&MockObject $activityConfig;
    private FieldCheckerInterface&MockObject $fieldChecker;
    private FieldTracker $fieldTracker;

    protected function setUp(): void
    {
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->themeConfig = $this->createMock(ThemeConfig::class);
        $this->config = $this->createMock(Config::class);
        $this->activityConfig = $this->createMock(ActivityConfigInterface::class);
        $this->fieldChecker = $this->createMock(FieldCheckerInterface::class);
        $this->fieldChecker->method('isFieldProtected')->willReturn(false);

        $this->fieldTracker = new FieldTracker(
            $this->systemConfig,
            $this->themeConfig,
            $this->config,
            $this->activityConfig,
            $this->fieldChecker
        );
    }

    public function testGetFieldsWithArrayReturnsArray(): void
    {
        $skipFields = ['field1', 'field2', 'field3'];

        $result = $this->fieldTracker->getFields($skipFields);

        $this->assertSame($skipFields, $result);
    }

    public function testGetFieldsWithEmptyArrayReturnsEmptyArray(): void
    {
        $result = $this->fieldTracker->getFields([]);

        $this->assertSame([], $result);
    }

    public function testGetFieldsWithInvalidMethodNameReturnsEmptyArray(): void
    {
        $result = $this->fieldTracker->getFields('nonExistentMethod');

        $this->assertSame([], $result);
    }

    public function testGetFieldsWithEmptyStringReturnsEmptyArray(): void
    {
        $result = $this->fieldTracker->getFields('');

        $this->assertSame([], $result);
    }

    public function testGetSkipEditFieldDataReturnsConfigFields(): void
    {
        $expectedFields = ['created_at', 'updated_at', 'form_key'];

        $this->config
            ->expects($this->once())
            ->method('getGlobalSkipEditFields')
            ->willReturn($expectedFields);

        $result = $this->fieldTracker->getSkipEditFieldData();

        $this->assertSame($expectedFields, $result);
    }

    public function testValidateValueReturnsTrueWhenFieldInSkipFields(): void
    {
        $model = new DataObject(['form_key' => 'abc123']);
        $skipFields = ['form_key', 'created_at'];

        $result = $this->fieldTracker->validateValue($model, 'form_key', 'abc123', $skipFields);

        $this->assertTrue($result);
    }

    public function testValidateValueReturnsFalseWhenFieldNotInSkipFields(): void
    {
        $model = new DataObject(['name' => 'Test']);
        $skipFields = ['form_key', 'created_at'];

        $result = $this->fieldTracker->validateValue($model, 'name', 'Test', $skipFields);

        $this->assertFalse($result);
    }

    public function testValidateValueReturnsTrueWhenValueIsArray(): void
    {
        $model = new DataObject(['options' => ['a', 'b']]);

        $result = $this->fieldTracker->validateValue($model, 'options', ['a', 'b'], []);

        $this->assertTrue($result);
    }

    public function testValidateValueReturnsTrueWhenValueIsObject(): void
    {
        $model = new DataObject([]);

        $result = $this->fieldTracker->validateValue($model, 'complex', new stdClass(), []);

        $this->assertTrue($result);
    }

    public function testValidateValueReturnsTrueWhenOrigDataIsArray(): void
    {
        $model = new DataObject(['categories' => 'test']);
        $model->setOrigData(['categories' => ['cat1', 'cat2']]);

        $result = $this->fieldTracker->validateValue($model, 'categories', 'test', []);

        $this->assertTrue($result);
    }

    public function testValidateValueReturnsFalseForStringNotInSkipFields(): void
    {
        $model = new DataObject(['sku' => 'TEST-SKU']);
        $model->setOrigData(['sku' => 'OLD-SKU']);

        $result = $this->fieldTracker->validateValue($model, 'sku', 'TEST-SKU', []);

        $this->assertFalse($result);
    }

    public function testGetAddDataWithArraySkipFields(): void
    {
        $model = new DataObject([
            'name' => 'Test Product',
            'sku' => 'TEST-123',
            'form_key' => 'abc123',
            'price' => '99.99'
        ]);

        $skipFields = ['form_key'];

        $result = $this->fieldTracker->getAddData($model, $skipFields);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('sku', $result);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayNotHasKey('form_key', $result);

        $this->assertSame('', $result['name']['old_value']);
        $this->assertSame('Test Product', $result['name']['new_value']);
    }

    public function testGetAddDataWithEmptyModelReturnsEmptyArray(): void
    {
        $model = new DataObject([]);

        $result = $this->fieldTracker->getAddData($model, []);

        $this->assertSame([], $result);
    }

    // --- getEditData: happy path ---

    public function testGetEditDataDetectsGenuineChange(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['name' => 'New Name']);
        $model->setOrigData(['name' => 'Old Name']);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayHasKey('name', $result);
        $this->assertSame('Old Name', $result['name']['old_value']);
        $this->assertSame('New Name', $result['name']['new_value']);
    }

    public function testGetEditDataIgnoresUnchangedField(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['name' => 'Same']);
        $model->setOrigData(['name' => 'Same']);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayNotHasKey('name', $result);
    }

    public function testGetEditDataSkipsFieldsInSkipList(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn(['updated_at']);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['updated_at' => '2026-01-02', 'name' => 'New']);
        $model->setOrigData(['updated_at' => '2026-01-01', 'name' => 'Old']);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayNotHasKey('updated_at', $result);
        $this->assertArrayHasKey('name', $result);
    }

    // --- getEditData: edge/boundary cases for type normalization ---

    public function testGetEditDataIgnoresIntZeroVsStringZero(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['sort_order' => '0']);
        $model->setOrigData(['sort_order' => 0]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayNotHasKey('sort_order', $result, 'int 0 vs string "0" should not be a change');
    }

    public function testGetEditDataIgnoresNullVsEmptyString(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['description' => '']);
        $model->setOrigData(['description' => null]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayNotHasKey('description', $result, 'null vs empty string should not be a change');
    }

    public function testGetEditDataIgnoresFalseVsEmptyString(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['is_active' => '']);
        $model->setOrigData(['is_active' => false]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayNotHasKey('is_active', $result, 'false vs empty string should not be a change');
    }

    public function testGetEditDataDetectsZeroToOneChange(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['status' => 1]);
        $model->setOrigData(['status' => 0]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayHasKey('status', $result, 'Change from 0 to 1 must be detected');
        $this->assertSame('0', $result['status']['old_value']);
        $this->assertSame('1', $result['status']['new_value']);
    }

    public function testGetEditDataDetectsOneToZeroChange(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['status' => 0]);
        $model->setOrigData(['status' => 1]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayHasKey('status', $result, 'Change from 1 to 0 must be detected');
        $this->assertSame('1', $result['status']['old_value']);
        $this->assertSame('0', $result['status']['new_value']);
    }

    public function testGetEditDataDetectsNullToValue(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['weight' => '5.00']);
        $model->setOrigData(['weight' => null]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayHasKey('weight', $result, 'Change from null to value must be detected');
        $this->assertSame('', $result['weight']['old_value']);
        $this->assertSame('5.00', $result['weight']['new_value']);
    }

    public function testGetEditDataDetectsValueToNull(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        // origData has value, current data has null — but getData() won't have a null
        // key in the loop. Simulate with empty string which is the typical form POST value.
        $model = new DataObject(['weight' => '']);
        $model->setOrigData(['weight' => '5.00']);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayHasKey('weight', $result, 'Change from value to empty must be detected');
        $this->assertSame('5.00', $result['weight']['old_value']);
        $this->assertSame('', $result['weight']['new_value']);
    }

    public function testGetEditDataIgnoresIntOneVsStringOne(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['is_active' => '1']);
        $model->setOrigData(['is_active' => 1]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayNotHasKey('is_active', $result, 'int 1 vs string "1" should not be a change');
    }

    public function testGetEditDataIgnoresFloatVsStringEquivalent(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['price' => '99.99']);
        $model->setOrigData(['price' => 99.99]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayNotHasKey('price', $result, 'float 99.99 vs string "99.99" should not be a change');
    }

    public function testGetEditDataIgnoresBothNullFields(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['special_price' => null]);
        $model->setOrigData(['special_price' => null]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayNotHasKey('special_price', $result, 'Both null should not be a change');
    }

    public function testGetEditDataWithEmptyModelReturnsEmpty(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject([]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertSame([], $result);
    }

    public function testGetEditDataMissingOrigDataKeyDetectsChange(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $model = new DataObject(['new_field' => 'value']);
        // origData has no 'new_field' key — getOrigData('new_field') returns null
        $model->setOrigData([]);

        $result = $this->fieldTracker->getEditData($model, []);

        $this->assertArrayHasKey('new_field', $result);
        $this->assertSame('', $result['new_field']['old_value']);
        $this->assertSame('value', $result['new_field']['new_value']);
    }

    public function testGetDeleteDataWithOrigData(): void
    {
        $model = new DataObject([]);
        $model->setOrigData([
            'name' => 'Test Product',
            'sku' => 'TEST-123',
            'form_key' => 'abc123'
        ]);

        $skipFields = ['form_key'];

        $result = $this->fieldTracker->getDeleteData($model, $skipFields);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('sku', $result);
        $this->assertArrayNotHasKey('form_key', $result);

        $this->assertSame('Test Product', $result['name']['old_value']);
        $this->assertSame('', $result['name']['new_value']);
    }

    // --- Sensitive field redaction ---

    public function testGetAddDataRedactsProtectedField(): void
    {
        $fieldChecker = $this->createMock(FieldCheckerInterface::class);
        $fieldChecker->method('isFieldProtected')
            ->willReturnCallback(fn(string $name) => $name === 'password');

        $tracker = new FieldTracker(
            $this->systemConfig,
            $this->themeConfig,
            $this->config,
            $this->activityConfig,
            $fieldChecker
        );

        $model = new DataObject(['password' => 'secret123', 'name' => 'Test']);

        $result = $tracker->getAddData($model, []);

        $this->assertSame('******', $result['password']['new_value']);
        $this->assertSame('', $result['password']['old_value']);
        $this->assertSame('Test', $result['name']['new_value']);
    }

    public function testGetEditDataRedactsProtectedField(): void
    {
        $this->config->method('getGlobalSkipEditFields')->willReturn([]);
        $this->activityConfig->method('isWildCardModel')->willReturn(false);

        $fieldChecker = $this->createMock(FieldCheckerInterface::class);
        $fieldChecker->method('isFieldProtected')
            ->willReturnCallback(fn(string $name) => $name === 'api_key');

        $tracker = new FieldTracker(
            $this->systemConfig,
            $this->themeConfig,
            $this->config,
            $this->activityConfig,
            $fieldChecker
        );

        $model = new DataObject(['api_key' => 'new-key-456']);
        $model->setOrigData(['api_key' => 'old-key-123']);

        $result = $tracker->getEditData($model, []);

        $this->assertSame('******', $result['api_key']['old_value']);
        $this->assertSame('******', $result['api_key']['new_value']);
    }

    public function testGetDeleteDataRedactsProtectedField(): void
    {
        $fieldChecker = $this->createMock(FieldCheckerInterface::class);
        $fieldChecker->method('isFieldProtected')
            ->willReturnCallback(fn(string $name) => $name === 'cc_number');

        $tracker = new FieldTracker(
            $this->systemConfig,
            $this->themeConfig,
            $this->config,
            $this->activityConfig,
            $fieldChecker
        );

        $model = new DataObject([]);
        $model->setOrigData(['cc_number' => '4111111111111111', 'name' => 'Test']);

        $result = $tracker->getDeleteData($model, []);

        $this->assertSame('******', $result['cc_number']['old_value']);
        $this->assertSame('', $result['cc_number']['new_value']);
        $this->assertSame('Test', $result['name']['old_value']);
    }
}
