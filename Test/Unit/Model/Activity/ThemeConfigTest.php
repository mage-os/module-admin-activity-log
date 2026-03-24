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
use MageOS\AdminActivityLog\Model\Activity\ThemeConfig;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;

class ThemeConfigTest extends TestCase
{
    private ConfigCollectionFactory&MockObject $configCollectionFactory;
    private RequestInterface&MockObject $request;
    private ThemeConfig $themeConfig;

    protected function setUp(): void
    {
        $this->configCollectionFactory = $this->createMock(ConfigCollectionFactory::class);
        $this->request = $this->createMock(RequestInterface::class);

        $this->themeConfig = new ThemeConfig(
            new DataObject(),
            $this->configCollectionFactory,
            $this->request
        );
    }

    // --- collectAdditionalData: happy path ---

    public function testCollectAdditionalDataDetectsGenuineChange(): void
    {
        $oldData = ['header_default_title' => 'Old Title'];
        $newData = ['header_default_title' => 'New Title'];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertArrayHasKey('design/header/default_title', $result);
        $this->assertSame('Old Title', $result['design/header/default_title']['old_value']);
        $this->assertSame('New Title', $result['design/header/default_title']['new_value']);
    }

    public function testCollectAdditionalDataIgnoresUnchangedField(): void
    {
        $oldData = ['header_default_title' => 'Same'];
        $newData = ['header_default_title' => 'Same'];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertEmpty($result);
    }

    public function testCollectAdditionalDataSkipsFieldsInFieldArray(): void
    {
        $oldData = ['header_default_title' => 'Old'];
        $newData = ['header_default_title' => 'New'];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, ['header_default_title']);

        $this->assertEmpty($result);
    }

    // --- collectAdditionalData: edge/boundary cases ---

    public function testCollectAdditionalDataIgnoresIntZeroVsStringZero(): void
    {
        $oldData = ['footer_absolute_footer' => 0];
        $newData = ['footer_absolute_footer' => '0'];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertEmpty($result, 'int 0 vs string "0" should not be a change');
    }

    public function testCollectAdditionalDataIgnoresNullVsEmptyString(): void
    {
        $oldData = ['footer_absolute_footer' => null];
        $newData = ['footer_absolute_footer' => ''];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertEmpty($result, 'null vs empty string should not be a change');
    }

    public function testCollectAdditionalDataDetectsZeroToOneChange(): void
    {
        $oldData = ['footer_absolute_footer' => 0];
        $newData = ['footer_absolute_footer' => '1'];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertNotEmpty($result, 'Change from 0 to 1 must be detected');
    }

    public function testCollectAdditionalDataHandlesMissingOldKey(): void
    {
        $oldData = [];
        $newData = ['header_new_field' => 'value'];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertArrayHasKey('design/header/new_field', $result);
        $this->assertSame('', $result['design/header/new_field']['old_value']);
        $this->assertSame('value', $result['design/header/new_field']['new_value']);
    }

    public function testCollectAdditionalDataIgnoresBothEmpty(): void
    {
        $oldData = ['footer_absolute_footer' => ''];
        $newData = ['footer_absolute_footer' => ''];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertEmpty($result);
    }
}
