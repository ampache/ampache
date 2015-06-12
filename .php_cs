<?php

use Symfony\CS\FixerInterface;

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('modules')
    ->exclude('nbproject')
    ->in(__DIR__)
    ->in(__DIR__ . '/modules/localplay')
    ->in(__DIR__ . '/modules/catalog')
    ->in(__DIR__ . '/modules/ampacheapi')
;

return Symfony\CS\Config\Config::create()
    ->finder($finder)
;