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

    // --- collectAdditionalData: image uploader fields posting file descriptors ---

    public function testCollectAdditionalDataIgnoresUntouchedImageUploaderField(): void
    {
        $oldData = ['header_logo_src' => 'stores/1/logo.png'];
        $newData = ['header_logo_src' => [
            [
                'url' => 'https://example.com/media/logo/stores/1/logo.png',
                'file' => 'stores/1/logo.png',
                'name' => 'logo.png',
                'size' => 1234,
            ]
        ]];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertEmpty($result, 'Re-posting an unchanged image descriptor is not a change');
    }

    public function testCollectAdditionalDataFallsBackToDescriptorNameWhenFileMissing(): void
    {
        $oldData = ['head_shortcut_icon' => 'favicon.png'];
        $newData = ['head_shortcut_icon' => [
            ['name' => 'favicon.png', 'url' => 'https://example.com/media/favicon.png']
        ]];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertEmpty($result);
    }

    public function testCollectAdditionalDataDetectsReplacedImage(): void
    {
        $oldData = ['header_logo_src' => 'old-logo.png'];
        $newData = ['header_logo_src' => [
            ['file' => 'new-logo.png', 'name' => 'new-logo.png', 'size' => 99]
        ]];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertArrayHasKey('design/header/logo_src', $result);
        $this->assertSame('old-logo.png', $result['design/header/logo_src']['old_value']);
        $this->assertSame('new-logo.png', $result['design/header/logo_src']['new_value']);
    }

    public function testCollectAdditionalDataDetectsClearedImage(): void
    {
        $oldData = ['header_logo_src' => 'logo.png'];
        $newData = ['header_logo_src' => []];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertArrayHasKey('design/header/logo_src', $result);
        $this->assertSame('logo.png', $result['design/header/logo_src']['old_value']);
        $this->assertSame('', $result['design/header/logo_src']['new_value']);
    }

    public function testCollectAdditionalDataJoinsListOfScalars(): void
    {
        $oldData = ['watermark_image_size' => 'a,b'];
        $newData = ['watermark_image_size' => ['a', 'b']];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertEmpty($result);
    }

    public function testCollectAdditionalDataEncodesAssociativeArray(): void
    {
        $oldData = ['header_logo_src' => 'logo.png'];
        $newData = ['header_logo_src' => ['delete' => '1', 'value' => 'logo.png']];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertArrayHasKey('design/header/logo_src', $result);
        $this->assertSame(
            '{"delete":"1","value":"logo.png"}',
            $result['design/header/logo_src']['new_value']
        );
    }

    /**
     * A hand-crafted POST can nest arrays arbitrarily deep. The cast must not
     * raise "Array to string conversion": with swissup/module-ignition
     * installed that warning becomes an ErrorException, SaveAfter aborts and
     * the activity is never logged.
     */
    public function testCollectAdditionalDataHandlesNestedArrayInDescriptor(): void
    {
        $oldData = ['header_logo_src' => 'logo.png'];
        $newData = ['header_logo_src' => [['file' => ['nested'], 'name' => ['nested']]]];

        $warnings = [];
        set_error_handler(
            static function (int $errno, string $errstr) use (&$warnings): bool {
                $warnings[] = $errstr;
                return true;
            },
            E_WARNING | E_NOTICE
        );

        try {
            $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings, 'Flattening must not raise a PHP warning');
        $this->assertArrayHasKey('design/header/logo_src', $result);
        $this->assertSame(
            '{"file":["nested"],"name":["nested"]}',
            $result['design/header/logo_src']['new_value']
        );
    }

    public function testCollectAdditionalDataUsesPlaceholderForUnserializableValue(): void
    {
        $oldData = ['header_logo_src' => 'logo.png'];
        $newData = ['header_logo_src' => ['broken' => "\xB1\x31"]];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertArrayHasKey('design/header/logo_src', $result);
        $this->assertSame('[unserializable]', $result['design/header/logo_src']['new_value']);
    }

    public function testCollectAdditionalDataDoesNotEscapeSlashesInEncodedValue(): void
    {
        $oldData = [];
        $newData = ['header_logo_src' => ['path' => 'stores/1/logo.png']];

        $result = $this->themeConfig->collectAdditionalData($oldData, $newData, []);

        $this->assertSame(
            '{"path":"stores/1/logo.png"}',
            $result['design/header/logo_src']['new_value']
        );
    }
}
