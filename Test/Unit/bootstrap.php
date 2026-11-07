<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

/**
 * Standalone bootstrap for this module's unit tests.
 *
 * The classes under test are exercised against Composer's autoloader, without booting Magento. Factories that
 * Magento generates (a "<Class>Factory" with a create() method) are generated on demand into a temporary
 * directory, so the tests can double them before anything has been compiled. The stubs never reach the
 * installation's own generated code directory, whose real factories still win when they exist.
 */

use Magento\Framework\Code\Generator\Io;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\TestFramework\Unit\Autoloader\FactoryGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\GeneratedClassesAutoloader;

$autoloadCandidates = [
    __DIR__ . '/../../../../autoload.php', // vendor/<v>/<m>/Test/Unit -> vendor/autoload.php
    __DIR__ . '/../../vendor/autoload.php', // module run standalone
    __DIR__ . '/../../../../../../vendor/autoload.php', // app/code/<V>/<M>/Test/Unit -> root vendor
];

$loader = null;
$vendorDir = null;
foreach ($autoloadCandidates as $candidate) {
    if (file_exists($candidate)) {
        $loader = require $candidate;
        $vendorDir = dirname($candidate);
        break;
    }
}

if (!$loader instanceof \Composer\Autoload\ClassLoader || $vendorDir === null) {
    throw new \RuntimeException('Unable to locate Composer autoloader for the test bootstrap.');
}

$loader->addPsr4('Hryvinskyi\\PageSpeed\\', dirname(__DIR__, 2));
$loader->addPsr4('Hryvinskyi\\PageSpeed\\Test\\Unit\\', __DIR__);

// When running inside a Magento installation, resolve factories already generated there first.
$generated = dirname($vendorDir) . '/generated/code';
if (is_dir($generated)) {
    $loader->add('', [$generated]);
}

// Anything still missing that follows the "<Class>Factory" convention is generated as a stub.
$generatedClassesAutoloader = new GeneratedClassesAutoloader(
    [new FactoryGenerator()],
    new Io(new File(), sys_get_temp_dir() . '/hryvinskyi-page-speed-unit-generated')
);
spl_autoload_register(static function (string $className) use ($generatedClassesAutoloader): void {
    $generatedClassesAutoloader->load($className);
});
