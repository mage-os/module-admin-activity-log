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

use PHPUnit\Framework\TestCase;

/**
 * Test ACL resource definitions for Admin Activity Log module
 */
class AclTest extends TestCase
{
    /**
     * ACL resource IDs defined in the module
     */
    public const ACL_ACTIVITY_CONFIG = 'MageOS_AdminActivityLog::activityConfig';
    public const ACL_ACTIVITY_HEAD = 'MageOS_AdminActivityLog::activity_head';
    public const ACL_ACTIVITY = 'MageOS_AdminActivityLog::activity';
    public const ACL_LOGIN_ACTIVITY = 'MageOS_AdminActivityLog::login_activity';
    public const ACL_CONFIGURATION = 'MageOS_AdminActivityLog::configuration';

    private \SimpleXMLElement $aclConfig;

    protected function setUp(): void
    {
        $aclPath = dirname(__DIR__, 3) . '/etc/acl.xml';
        $this->assertFileExists($aclPath, 'ACL configuration file must exist');

        $content = file_get_contents($aclPath);
        $this->assertIsString($content);

        $xml = simplexml_load_string($content);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $this->aclConfig = $xml;
    }

    public function testAclFileIsValidXml(): void
    {
        $this->assertInstanceOf(\SimpleXMLElement::class, $this->aclConfig);
    }

    public function testActivityConfigResourceExists(): void
    {
        $resource = $this->findResourceById(self::ACL_ACTIVITY_CONFIG);

        $this->assertNotNull($resource, 'Activity config ACL resource must exist');
        $this->assertEquals('Admin Activity Section', (string)$resource['title']);
    }

    public function testActivityHeadResourceExists(): void
    {
        $resource = $this->findResourceById(self::ACL_ACTIVITY_HEAD);

        $this->assertNotNull($resource, 'Activity head ACL resource must exist');
        $this->assertEquals('Admin Activity', (string)$resource['title']);
        $this->assertEquals('100', (string)$resource['sortOrder']);
    }

    public function testActivityResourceExists(): void
    {
        $resource = $this->findResourceById(self::ACL_ACTIVITY);

        $this->assertNotNull($resource, 'Activity ACL resource must exist');
        $this->assertEquals('View', (string)$resource['title']);
        $this->assertEquals('10', (string)$resource['sortOrder']);
    }

    public function testLoginActivityResourceExists(): void
    {
        $resource = $this->findResourceById(self::ACL_LOGIN_ACTIVITY);

        $this->assertNotNull($resource, 'Login activity ACL resource must exist');
        $this->assertEquals('Login Activity', (string)$resource['title']);
        $this->assertEquals('20', (string)$resource['sortOrder']);
    }

    public function testConfigurationResourceExists(): void
    {
        $resource = $this->findResourceById(self::ACL_CONFIGURATION);

        $this->assertNotNull($resource, 'Configuration ACL resource must exist');
        $this->assertEquals('Settings', (string)$resource['title']);
        $this->assertEquals('40', (string)$resource['sortOrder']);
    }

    public function testActivityConfigIsUnderSystemConfiguration(): void
    {
        $configResource = $this->findResourceById('Magento_Config::config');
        $this->assertNotNull($configResource, 'Magento config resource must exist');

        $activityConfigResource = $configResource->xpath(
            ".//resource[@id='" . self::ACL_ACTIVITY_CONFIG . "']"
        );
        $this->assertNotEmpty(
            $activityConfigResource,
            'Activity config must be under Magento_Config::config'
        );
    }

    public function testAllActivityResourcesUnderActivityHead(): void
    {
        $activityHeadResource = $this->findResourceById(self::ACL_ACTIVITY_HEAD);
        $this->assertNotNull($activityHeadResource);

        $childResources = [
            self::ACL_ACTIVITY,
            self::ACL_LOGIN_ACTIVITY,
            self::ACL_CONFIGURATION
        ];

        foreach ($childResources as $resourceId) {
            $resource = $activityHeadResource->xpath(".//resource[@id='" . $resourceId . "']");
            $this->assertNotEmpty(
                $resource,
                sprintf('Resource %s must be under activity_head', $resourceId)
            );
        }
    }

    /**
     * Find resource element by ID in the ACL configuration
     */
    private function findResourceById(string $resourceId): ?\SimpleXMLElement
    {
        $result = $this->aclConfig->xpath("//resource[@id='" . $resourceId . "']");

        return !empty($result) ? $result[0] : null;
    }
}
