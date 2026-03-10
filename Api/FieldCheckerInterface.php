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

namespace MageOS\AdminActivityLog\Api;

/**
 * Service class for checking if fields contain sensitive data that should not be logged
 */
interface FieldCheckerInterface
{
    /**
     * Get array of protected fields that should not be logged
     *
     * @return string[]
     */
    public function getProtectedFields(): array;

    /**
     * Check if a field is protected from logging
     *
     * @param string $fieldName
     * @return bool
     */
    public function isFieldProtected(string $fieldName): bool;
}
