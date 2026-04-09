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

namespace MageOS\AdminActivityLog\Model;

use InvalidArgumentException;
use Magento\Framework\Model\AbstractModel;
use MageOS\AdminActivityLog\Api\ModelResolverInterface;

/**
 * Model resolver for dynamic model loading
 *
 * Resolves and instantiates models via a DI-injected factory map keyed by FQCN.
 * Only classes explicitly registered in the factory map can be instantiated,
 * which enforces the security allowlist without requiring ObjectManager.
 */
class ModelResolver implements ModelResolverInterface
{
    /**
     * @param array<class-string, object> $modelFactories Map of FQCN => factory instance (must expose create())
     */
    public function __construct(
        private readonly array $modelFactories = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getModel(string $className): AbstractModel
    {
        $className = str_replace('\\Interceptor', '', $className);

        if (!isset($this->modelFactories[$className])) {
            throw new InvalidArgumentException(
                sprintf('Class "%s" is not in the allowed model classes list', $className)
            );
        }

        return $this->modelFactories[$className]->create();
    }

    /**
     * @inheritDoc
     */
    public function loadModel(string $className, int|string $entityId, ?string $field = null): AbstractModel
    {
        $model = $this->getModel($className);

        if ($field !== null) {
            $model->load($entityId, $field);
        } else {
            $model->load($entityId);
        }

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function isValidModelClass(string $className): bool
    {
        return $this->isAllowedModelClass($className);
    }

    /**
     * @inheritDoc
     */
    public function isAllowedModelClass(string $className): bool
    {
        $className = str_replace('\\Interceptor', '', $className);

        return isset($this->modelFactories[$className]);
    }
}
