<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/tests',
        __DIR__ . '/src/Application',
        __DIR__ . '/src/Gui',
        __DIR__ . '/src/Plugin',
        __DIR__ . '/src/Repository',
    ])
    ->withCache(__DIR__ . '/build/rector', FileCacheStorage::class)
    ->withImportNames()
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class
    ])
    ->withPhpSets(php82: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, codingStyle: true)
    ->withSkip([
        FlipTypeControlToUseExclusiveTypeRector::class,
        StaticClosureRector::class,
        SymplifyQuoteEscapeRector::class,
        __DIR__ . '/src/Repository/Model',
    ]);
