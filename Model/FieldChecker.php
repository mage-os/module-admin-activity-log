<?php

declare(strict_types=1);

/**
 * MageOS
 *
 * @category   MageOS
 * @package    MageOS_AdminActivityLog
 * @copyright  Copyright (C) 2025 MageOS (https://mage-os.org/)
 * @license    https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace MageOS\AdminActivityLog\Model;

use MageOS\AdminActivityLog\Api\FieldCheckerInterface;

class FieldChecker implements FieldCheckerInterface
{
    /**
     * @param string[] $protectedFields Sensitive fields that should never be logged
     */
    public function __construct(
        protected readonly array $protectedFields = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getProtectedFields(): array
    {
        return $this->protectedFields;
    }

    /**
     * @inheritDoc
     */
    public function isFieldProtected(string $fieldName): bool
    {
        return in_array($fieldName, $this->protectedFields, true);
    }
}
