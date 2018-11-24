<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('/components')
    ->exclude('/vendor')
    ->exclude('/nbproject')
	->exclude('/storage')
    ->exclude('/resources')
    ->notPath('src/Symfony/Component/Translation/Tests/fixtures/resources.php')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'strict_param' => false,
		'no_trailing_whitespace' => true,
		'blank_line_before_return' => true,
		'full_opening_tag' => true,
		'braces' => true,
		'single_blank_line_at_eof' => true,
		'visibility_required' => true,
		'binary_operator_spaces' => ['align_equals' => true],
		'concat_space' => ['spacing' => 'one'],
		'elseif' => true,
		'blank_line_after_namespace' => true,
		'lowercase_constants' => true,
		'encoding' => true,
     ])
   ->setIndent("    ")
   ->setUsingCache(false)
   ->setFinder($finder)
;

/*
--fixers=indentation,linefeed,trailing_spaces,short_tag,braces,controls_spaces,
eof_ending,visibility,align_equals,concat_with_spaces,elseif,line_after_namespace,lowercase_constants,encoding .
*/
