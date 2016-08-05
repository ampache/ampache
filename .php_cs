<?php

use Symfony\CS\FixerInterface;

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('lib/components')
    ->exclude('lib/vendor')
    ->exclude('modules')
    ->exclude('nbproject')
    ->in(__DIR__)
    ->in(__DIR__ . '/modules/localplay')
    ->in(__DIR__ . '/modules/catalog')
    ->in(__DIR__ . '/modules/plugins')
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->finder($finder)
    ->setUsingCache(true)
;
