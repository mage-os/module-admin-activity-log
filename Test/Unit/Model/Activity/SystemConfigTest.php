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

namespace MageOS\AdminActivityLog\Test\Unit\Model\Activity;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use MageOS\AdminActivityLog\Model\Activity\SystemConfig;
use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Section;
use Magento\Config\Model\Config\Structure\Element\Tab;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\DataObject;

class SystemConfigTest extends TestCase
{
    private Structure&MockObject $configStructure;
    private ScopeConfigInterface&MockObject $scopeConfig;
    private ValueFactory&MockObject $valueFactory;
    private SystemConfig $systemConfig;

    protected function setUp(): void
    {
        $this->configStructure = $this->createMock(Structure::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->valueFactory = $this->createMock(ValueFactory::class);

        $this->systemConfig = new SystemConfig(
            new DataObject(),
            $this->valueFactory,
            $this->configStructure,
            $this->scopeConfig
        );
    }

    // --- getPath ---

    public function testGetPathExtractsFirstSegment(): void
    {
        $model = new DataObject(['path' => 'web/unsecure/base_url']);
        $this->assertSame('web', $this->systemConfig->getPath($model));
    }

    public function testGetPathReturnsEmptyStringWhenNoPath(): void
    {
        $model = new DataObject();
        $this->assertSame('', $this->systemConfig->getPath($model));
    }

    public function testGetPathHandlesSingleSegmentPath(): void
    {
        $model = new DataObject(['path' => 'general']);
        $this->assertSame('general', $this->systemConfig->getPath($model));
    }

    // --- getHumanReadablePath ---

    public function testGetHumanReadablePathWithValidSection(): void
    {
        $tab = $this->createMock(Tab::class);
        $tab->method('getId')->willReturn('general_tab');
        $tab->method('getLabel')->willReturn('General');

        $section = $this->createMock(Section::class);
        $section->method('getAttribute')->with('tab')->willReturn('general_tab');
        $section->method('getLabel')->willReturn('Web');

        $this->configStructure->method('getElement')
            ->with('web')
            ->willReturn($section);

        $this->configStructure->method('getTabs')
            ->willReturn(new \ArrayIterator([$tab]));

        $result = $this->systemConfig->getHumanReadablePath('web/unsecure/base_url');

        $this->assertStringContainsString('System Configuration', $result);
        $this->assertStringContainsString('General', $result);
        $this->assertStringContainsString('Web', $result);
        $this->assertStringContainsString(' > ', $result);
    }

    public function testGetHumanReadablePathFallsBackToRawPathWhenNotSection(): void
    {
        $nonSection = $this->createMock(Structure\Element\Group::class);

        $this->configStructure->method('getElement')
            ->with('invalid')
            ->willReturn($nonSection);

        $result = $this->systemConfig->getHumanReadablePath('invalid/group/field');

        $this->assertSame('invalid/group/field', $result);
    }

    // --- getEditData ---

    public function testGetEditDataDetectsChange(): void
    {
        $model = new DataObject([
            'path' => 'web/unsecure/base_url',
            'value' => 'https://new.example.com/',
        ]);
        $model->setOrigData([
            'unsecure' => [
                'fields' => [
                    'base_url' => ['value' => 'https://old.example.com/'],
                ],
            ],
        ]);

        $result = $this->systemConfig->getEditData($model, []);

        $this->assertArrayHasKey('web/unsecure/base_url', $result);
        $this->assertSame('https://old.example.com/', $result['web/unsecure/base_url']['old_value']);
        $this->assertSame('https://new.example.com/', $result['web/unsecure/base_url']['new_value']);
    }

    public function testGetEditDataReturnsEmptyWhenNoChange(): void
    {
        $model = new DataObject([
            'path' => 'web/unsecure/base_url',
            'value' => 'https://same.example.com/',
        ]);
        $model->setOrigData([
            'unsecure' => [
                'fields' => [
                    'base_url' => ['value' => 'https://same.example.com/'],
                ],
            ],
        ]);

        $result = $this->systemConfig->getEditData($model, []);

        $this->assertEmpty($result);
    }

    public function testGetEditDataReturnsEmptyWhenPathHasLessThanThreeParts(): void
    {
        $model = new DataObject([
            'path' => 'web/unsecure',
            'value' => 'something',
        ]);

        $result = $this->systemConfig->getEditData($model, []);

        $this->assertEmpty($result);
    }

    public function testGetEditDataFallsBackToScopeConfigForOldValue(): void
    {
        $model = new DataObject([
            'path' => 'web/unsecure/base_url',
            'value' => 'https://new.example.com/',
        ]);
        // No origData set — will be null

        $this->scopeConfig->method('getValue')
            ->with('web/unsecure/base_url')
            ->willReturn('https://scope-old.example.com/');

        $result = $this->systemConfig->getEditData($model, []);

        $this->assertArrayHasKey('web/unsecure/base_url', $result);
        $this->assertSame('https://scope-old.example.com/', $result['web/unsecure/base_url']['old_value']);
        $this->assertSame('https://new.example.com/', $result['web/unsecure/base_url']['new_value']);
    }

    public function testGetEditDataFlattensListArrayToCommaSeparated(): void
    {
        $model = new DataObject([
            'path' => 'general/country/allow',
            'value' => ['US', 'CA', 'MX'],
        ]);
        $model->setOrigData([
            'country' => [
                'fields' => [
                    'allow' => ['value' => 'US'],
                ],
            ],
        ]);

        $result = $this->systemConfig->getEditData($model, []);

        $this->assertArrayHasKey('general/country/allow', $result);
        $this->assertSame('US,CA,MX', $result['general/country/allow']['new_value']);
    }

    public function testGetEditDataFlattensNestedArrayToJson(): void
    {
        $model = new DataObject([
            'path' => 'general/country/allow',
            'value' => ['key' => 'value'],
        ]);
        $model->setOrigData([
            'country' => [
                'fields' => [
                    'allow' => ['value' => 'old'],
                ],
            ],
        ]);

        $result = $this->systemConfig->getEditData($model, []);

        $this->assertArrayHasKey('general/country/allow', $result);
        $this->assertSame('{"key":"value"}', $result['general/country/allow']['new_value']);
    }
}
