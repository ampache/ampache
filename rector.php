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
    ]);
