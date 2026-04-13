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

namespace MageOS\AdminActivityLog\Test\Unit\Model\Config;

use DOMDocument;
use MageOS\AdminActivityLog\Model\Config\Converter;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    private Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new Converter();
    }

    private function createDom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml);
        return $dom;
    }

    public function testConvertExtractsActionsFromXml(): void
    {
        $dom = $this->createDom(
            '<config>
                <actions>
                    <action id="view"><label>View</label></action>
                    <action id="edit"><label>Edit</label></action>
                    <action id="delete"><label>Delete</label></action>
                </actions>
            </config>'
        );

        $result = $this->converter->convert($dom);

        $this->assertSame(
            ['view' => 'View', 'edit' => 'Edit', 'delete' => 'Delete'],
            $result['config']['actions']
        );
    }

    public function testConvertExtractsGlobalSkipEditFields(): void
    {
        $dom = $this->createDom(
            '<config>
                <skip_edit_fields>
                    <field>form_key</field>
                    <field>key</field>
                    <field>updated_at</field>
                </skip_edit_fields>
            </config>'
        );

        $result = $this->converter->convert($dom);

        $this->assertSame(
            ['form_key', 'key', 'updated_at'],
            $result['config']['skip_edit_fields']
        );
    }

    public function testConvertExtractsModuleWithLabel(): void
    {
        $dom = $this->createDom(
            '<config>
                <modules>
                    <module name="Magento_Catalog">
                        <label>Catalog</label>
                    </module>
                </modules>
            </config>'
        );

        $result = $this->converter->convert($dom);

        $this->assertSame('Catalog', $result['config']['Magento_Catalog']['label']);
    }

    public function testConvertExtractsModuleModels(): void
    {
        $dom = $this->createDom(
            '<config>
                <modules>
                    <module name="Magento_Catalog">
                        <label>Catalog</label>
                        <models>
                            <class name="Magento\Catalog\Model\Product"/>
                            <class name="Magento\Catalog\Model\Category"/>
                        </models>
                    </module>
                </modules>
            </config>'
        );

        $result = $this->converter->convert($dom);

        $this->assertSame(
            ['Magento\Catalog\Model\Product', 'Magento\Catalog\Model\Category'],
            $result['config']['Magento_Catalog']['model']
        );
    }

    public function testConvertExtractsModuleEvents(): void
    {
        $dom = $this->createDom(
            '<config>
                <modules>
                    <module name="Magento_Catalog">
                        <label>Catalog</label>
                        <events>
                            <event controller_action="catalog_product_save" action_alias="edit"/>
                            <event controller_action="catalog_product_delete" action_alias="delete"/>
                        </events>
                    </module>
                </modules>
            </config>'
        );

        $result = $this->converter->convert($dom);

        $expected = [
            'catalog_product_save' => [
                'action' => 'edit',
                'module' => 'Magento_Catalog',
            ],
            'catalog_product_delete' => [
                'action' => 'delete',
                'module' => 'Magento_Catalog',
            ],
        ];
        $this->assertSame($expected, $result['config']['Magento_Catalog']['actions']);
    }

    public function testConvertExtractsPostDispatchFromEvent(): void
    {
        $dom = $this->createDom(
            '<config>
                <modules>
                    <module name="Magento_Catalog">
                        <label>Catalog</label>
                        <events>
                            <event controller_action="catalog_product_save"
                                   action_alias="edit"
                                   post_dispatch="catalog_product_edit"/>
                        </events>
                    </module>
                </modules>
            </config>'
        );

        $result = $this->converter->convert($dom);

        $this->assertSame(
            'catalog_product_edit',
            $result['config']['Magento_Catalog']['actions']['catalog_product_save']['post_dispatch']
        );
    }

    public function testConvertExtractsModuleConfig(): void
    {
        $dom = $this->createDom(
            '<config>
                <modules>
                    <module name="Magento_Catalog">
                        <label>Catalog</label>
                        <config>
                            <trackfield method="getId"/>
                            <configpath constant="XML_PATH_CATALOG"/>
                            <editurl url="catalog/product/edit"/>
                            <itemfield field="product_id"/>
                        </config>
                    </module>
                </modules>
            </config>'
        );

        $result = $this->converter->convert($dom);
        $config = $result['config']['Magento_Catalog']['config'];

        $this->assertSame('getId', $config['trackfield']);
        $this->assertSame('XML_PATH_CATALOG', $config['configpath']);
        $this->assertSame('catalog/product/edit', $config['editurl']);
        $this->assertSame('product_id', $config['itemfield']);
    }

    public function testConvertExtractsModuleSkipFields(): void
    {
        $dom = $this->createDom(
            '<config>
                <modules>
                    <module name="Magento_Catalog">
                        <label>Catalog</label>
                        <config>
                            <skip_fields>
                                <field>updated_at</field>
                                <field>created_at</field>
                            </skip_fields>
                        </config>
                    </module>
                </modules>
            </config>'
        );

        $result = $this->converter->convert($dom);

        $this->assertSame(
            ['updated_at', 'created_at'],
            $result['config']['Magento_Catalog']['config']['skip_fields']
        );
    }

    public function testConvertHandlesEmptyModulesNode(): void
    {
        $dom = $this->createDom(
            '<config>
                <actions>
                    <action id="view"><label>View</label></action>
                </actions>
                <skip_edit_fields>
                    <field>form_key</field>
                </skip_edit_fields>
                <modules/>
            </config>'
        );

        $result = $this->converter->convert($dom);

        $this->assertSame(['view' => 'View'], $result['config']['actions']);
        $this->assertSame(['form_key'], $result['config']['skip_edit_fields']);
        $this->assertCount(2, $result['config'], 'Only actions and skip_edit_fields should be present');
    }

    public function testConvertHandlesEventWithoutPostDispatch(): void
    {
        $dom = $this->createDom(
            '<config>
                <modules>
                    <module name="Magento_Cms">
                        <label>CMS</label>
                        <events>
                            <event controller_action="cms_page_save" action_alias="edit"/>
                        </events>
                    </module>
                </modules>
            </config>'
        );

        $result = $this->converter->convert($dom);

        $eventData = $result['config']['Magento_Cms']['actions']['cms_page_save'];
        $this->assertSame('edit', $eventData['action']);
        $this->assertSame('Magento_Cms', $eventData['module']);
        $this->assertArrayNotHasKey('post_dispatch', $eventData);
    }
}
