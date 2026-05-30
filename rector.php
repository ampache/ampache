<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src/Application',
        __DIR__ . '/src/Config/Init',
        __DIR__ . '/src/Gui',
        __DIR__ . '/src/Module',
        __DIR__ . '/src/Plugin',
        __DIR__ . '/src/Repository',
        __DIR__ . '/tests',
    ])
    ->withCache(__DIR__ . '/build/rector', FileCacheStorage::class)
    ->withImportNames()
    ->withPhpSets(php85: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, codingStyle: true)
    ->withSkip([
        FlipTypeControlToUseExclusiveTypeRector::class,
        __DIR__ . '/src/Module/Api',
        __DIR__ . '/src/Module/Util/Captcha',
        __DIR__ . '/src/Module/System/Update/Migration',
        __DIR__ . '/src/Repository/Model',
    ]);
