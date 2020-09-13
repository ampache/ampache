<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('master')
    ->exclude('develop')
    ->exclude('core')
    ->exclude('lib/components')
    ->exclude('lib/vendor')
    ->exclude('modules')
    ->exclude('modules/captcha')
    ->exclude('modules/oauth')
    ->exclude('nbproject')
    ->notPath('src/Symfony/Component/Translation/Tests/fixtures/resources.php')
    ->in(__DIR__)
    ->in(__DIR__ . '/modules/localplay')
    ->in(__DIR__ . '/modules/catalog')
    ->in(__DIR__ . '/modules/plugins')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'strict_param' => false,
        'no_trailing_whitespace' => true,
        'blank_line_before_return' => true,
        'full_opening_tag' => true,
        'braces' => ['allow_single_line_closure' => true],
        'single_blank_line_at_eof' => true,
        'visibility_required' => true,
        'binary_operator_spaces' => ['align_equals' => true],
        'concat_space' => ['spacing' => 'one'],
        'elseif' => true,
        'blank_line_after_namespace' => true,
        'lowercase_constants' => true,
        'encoding' => true,
        'no_break_comment' => false,
        'method_argument_space' => false,
        'line_ending' => true,
     ])
   ->setIndent("    ")
   ->setUsingCache(false)
   ->setFinder($finder)
   ->setLineEnding("\n")
;

