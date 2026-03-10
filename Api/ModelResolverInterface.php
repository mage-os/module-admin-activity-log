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

namespace MageOS\AdminActivityLog\Api;

use Magento\Framework\Model\AbstractModel;

/**
 * Interface for resolving and loading models dynamically
 *
 * This interface provides a clean abstraction for dynamically loading models
 * by class name, replacing direct ObjectManager usage in repositories.
 *
 * @api
 */
interface ModelResolverInterface
{
    /**
     * Get a model instance by class name
     *
     * @param string $className Fully qualified class name
     * @return AbstractModel
     */
    public function getModel(string $className): AbstractModel;

    /**
     * Load a model by class name and entity ID
     *
     * @param string $className Fully qualified class name
     * @param int|string $entityId The entity ID to load
     * @param string|null $field Optional field name for loading (defaults to primary key)
     * @return AbstractModel
     */
    public function loadModel(string $className, int|string $entityId, ?string $field = null): AbstractModel;

    /**
     * Check if a class name is a valid loadable model
     *
     * @param string $className Fully qualified class name
     * @return bool
     */
    public function isValidModelClass(string $className): bool;

    /**
     * Check if a class name is in the allowed model classes list
     *
     * This provides an additional security layer to prevent instantiation
     * of arbitrary model classes during model loading operations.
     *
     * @param string $className Fully qualified class name
     * @return bool
     */
    public function isAllowedModelClass(string $className): bool;
}
