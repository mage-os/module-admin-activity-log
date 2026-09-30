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
     * Build a config value model as it reaches SaveAfter for an existing row
     *
     * Mirrors BackendModelFactory (stored row and field config in orig data,
     * field name in field_config) and SaveBefore, which merges the section's
     * config groups from SystemConfig::getOldData() into orig data.
     *
     * @param mixed $storedValue Value of the core_config_data row (may be NULL)
     * @param mixed $newValue Value after the backend model's beforeSave()
     */
    private function createValueModel(
        string $path,
        mixed $storedValue,
        mixed $newValue,
        string $scope = 'stores',
        int $scopeId = 1,
        ?string $fieldName = null
    ): Value {
        $fieldConfig = [
            'path' => $path,
            'field' => $fieldName ?? str_replace('/', '_', substr($path, strlen('design/'))),
        ];
        $storedRow = [
            'config_id' => 42,
            'scope' => $scope,
            'scope_id' => $scopeId,
            'path' => $path,
            'value' => $storedValue,
        ];

        $model = $this->getMockBuilder(Value::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $model->setData(array_merge($storedRow, ['field_config' => $fieldConfig, 'value' => $newValue]));
        foreach ($storedRow as $key => $value) {
            $model->setOrigData($key, $value);
        }
        $model->setOrigData('field_config', $fieldConfig);
        $model->setOrigData('head', ['fields' => ['default_title' => ['value' => 'Other scope title']]]);

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

    public function testGetEditDataIgnoresNullRowRepostedAsEmptyString(): void
    {
        // Storage::load() turns NULL into '', so the form re-posts '' and the row is saved
        $model = $this->createValueModel('design/footer/absolute_footer', null, '');

        $this->assertSame([], $this->themeConfig->getEditData($model, []));
    }

    public function testGetEditDataDetectsZeroToOneChange(): void
    {
        $model = $this->createValueModel('design/footer/absolute_footer', '0', '1');

        $this->assertNotEmpty($this->themeConfig->getEditData($model, []));
    }

    public function testGetEditDataDetectsChangeFromNullRow(): void
    {
        $model = $this->createValueModel('design/header/welcome', null, 'Hello');

        $result = $this->themeConfig->getEditData($model, []);

        $this->assertSame(['old_value' => '', 'new_value' => 'Hello'], $result['design/header/welcome']);
    }

    public function testGetEditDataSkipsFieldByFormNameNotDerivableFromPath(): void
    {
        $model = $this->createValueModel(
            'design/search_engine_robots/default_robots',
            'INDEX,FOLLOW',
            'NOINDEX,NOFOLLOW',
            fieldName: 'default_robots'
        );

        $this->assertSame([], $this->themeConfig->getEditData($model, ['default_robots']));
    }

    public function testGetEditDataIgnoresConfigGroupsMergedIntoOrigData(): void
    {
        $model = $this->createValueModel('design/head/default_title', 'Stored title', 'Stored title');

        $this->assertSame([], $this->themeConfig->getEditData($model, []));
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
