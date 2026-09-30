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
use Magento\Framework\App\Config\Value;

class ThemeConfigTest extends TestCase
{
    private ThemeConfig $themeConfig;

    protected function setUp(): void
    {
        $this->themeConfig = new ThemeConfig();
    }

    /**
     * Build a config value model the way BackendModelFactory does for a stored field
     *
     * @param mixed $storedValue Value held in core_config_data, null if no row exists
     * @param mixed $newValue Value after the backend model's beforeSave()
     */
    private function createValueModel(
        string $path,
        mixed $storedValue,
        mixed $newValue,
        string $scope = 'stores',
        int $scopeId = 1
    ): Value {
        $model = $this->getMockBuilder(Value::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $model->setData([
            'path' => $path,
            'scope' => $scope,
            'scope_id' => $scopeId,
            'value' => $newValue,
        ]);
        if ($storedValue !== null) {
            $model->setOrigData('value', $storedValue);
        }

        return $model;
    }

    // --- getEditData: happy path ---

    public function testGetEditDataDetectsGenuineChange(): void
    {
        $model = $this->createValueModel('design/head/default_title', 'Old Title', 'New Title');

        $result = $this->themeConfig->getEditData($model, []);

        $this->assertSame(
            ['design/head/default_title' => ['old_value' => 'Old Title', 'new_value' => 'New Title']],
            $result
        );
    }

    public function testGetEditDataIgnoresUnchangedField(): void
    {
        $model = $this->createValueModel('design/head/default_title', 'Same', 'Same');

        $this->assertSame([], $this->themeConfig->getEditData($model, []));
    }

    public function testGetEditDataSkipsFieldsInFieldArray(): void
    {
        $model = $this->createValueModel('design/head/includes', '<link/>', '<script/>');

        $this->assertSame([], $this->themeConfig->getEditData($model, ['head_includes', 'scope']));
    }

    public function testGetEditDataSetsEditUrlIdFromModelScope(): void
    {
        $model = $this->createValueModel('design/head/default_title', 'Old', 'New', 'websites', 2);

        $this->themeConfig->getEditData($model, []);

        $this->assertSame('websites/scope_id/2', $model->getId());
    }

    // --- getEditData: image uploader fields ---

    public function testGetEditDataLogsStoredPathOfReplacedImage(): void
    {
        // File::beforeSave() has already moved the upload and set the scoped path
        $model = $this->createValueModel('design/header/logo_src', 'stores/1/old-logo.png', 'stores/1/new-logo.png');

        $result = $this->themeConfig->getEditData($model, []);

        $this->assertSame('stores/1/old-logo.png', $result['design/header/logo_src']['old_value']);
        $this->assertSame('stores/1/new-logo.png', $result['design/header/logo_src']['new_value']);
    }

    public function testGetEditDataIgnoresUntouchedImage(): void
    {
        $model = $this->createValueModel('design/header/logo_src', 'stores/1/logo.png', 'stores/1/logo.png');

        $this->assertSame([], $this->themeConfig->getEditData($model, []));
    }

    // --- getEditData: edge/boundary cases ---

    public function testGetEditDataIgnoresIntZeroVsStringZero(): void
    {
        $model = $this->createValueModel('design/footer/absolute_footer', 0, '0');

        $this->assertSame([], $this->themeConfig->getEditData($model, []));
    }

    public function testGetEditDataIgnoresMissingStoredValueVsEmptyString(): void
    {
        $model = $this->createValueModel('design/footer/absolute_footer', null, '');

        $this->assertSame([], $this->themeConfig->getEditData($model, []));
    }

    public function testGetEditDataDetectsZeroToOneChange(): void
    {
        $model = $this->createValueModel('design/footer/absolute_footer', '0', '1');

        $this->assertNotEmpty($this->themeConfig->getEditData($model, []));
    }

    public function testGetEditDataHandlesMissingStoredValue(): void
    {
        $model = $this->createValueModel('design/header/welcome', null, 'Hello');

        $result = $this->themeConfig->getEditData($model, []);

        $this->assertSame('', $result['design/header/welcome']['old_value']);
        $this->assertSame('Hello', $result['design/header/welcome']['new_value']);
    }

    public function testGetEditDataEncodesArrayValue(): void
    {
        $model = $this->createValueModel('design/pagination/list', 'a', ['path' => 'stores/1/a']);

        $result = $this->themeConfig->getEditData($model, []);

        $this->assertSame('{"path":"stores/1/a"}', $result['design/pagination/list']['new_value']);
    }

    public function testGetEditDataUsesPlaceholderForUnserializableValue(): void
    {
        $model = $this->createValueModel('design/pagination/list', 'a', ['broken' => "\xB1\x31"]);

        $result = $this->themeConfig->getEditData($model, []);

        $this->assertSame('[unserializable]', $result['design/pagination/list']['new_value']);
    }

    // --- getOldData ---

    public function testGetOldDataReturnsStoredValueForModelPath(): void
    {
        $model = $this->createValueModel('design/head/default_title', 'Stored', 'New');

        $this->assertSame(['design/head/default_title' => 'Stored'], $this->themeConfig->getOldData($model));
    }
}
