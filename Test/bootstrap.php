<?php
/**
 * Bootstrap for standalone unit tests (CI).
 *
 * Registers an autoloader that generates stub Factory classes on the fly,
 * so tests can mock them without running setup:di:compile.
 */
declare(strict_types=1);

spl_autoload_register(function (string $className): void {
    if (!str_ends_with($className, 'Factory')) {
        return;
    }

    $sourceName = substr($className, 0, -strlen('Factory'));
    if ($sourceName === '' || !class_exists($sourceName) && !interface_exists($sourceName)) {
        return;
    }

    $parts = explode('\\', $className);
    $shortName = array_pop($parts);
    $namespace = implode('\\', $parts);

    $code = '';
    if ($namespace !== '') {
        $code .= "namespace {$namespace};\n\n";
    }
    $code .= "class {$shortName}\n{\n";
    $code .= "    public function create(array \$data = [])\n    {\n    }\n";
    $code .= "}\n";

    // eval is intentional: generates stub classes for test mocking,
    // same approach as Magento's GeneratedClassesAutoloader
    eval($code); // phpcs:ignore Squiz.PHP.Eval
});
