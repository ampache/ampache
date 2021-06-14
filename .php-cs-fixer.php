<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['vendor', 'lib/components', 'public/foam', 'public/ample', 'public/client', 'public/newclient'])
    ->in('public/')
    ->in('src/')
    ->in('tests/')
;

$config = new PhpCsFixer\Config();
$config->setRules(
    [
        '@PSR2' => true,
        'strict_param' => false,
        'no_trailing_whitespace' => true,
        'blank_line_before_statement' => ['statements' => ['return']],
        'full_opening_tag' => true,
        'braces' => ['allow_single_line_closure' => true],
        'single_blank_line_at_eof' => true,
        'visibility_required' => true,
        'binary_operator_spaces' => ['operators' => ['=' => 'align']],
        'concat_space' => ['spacing' => 'one'],
        'elseif' => true,
        'blank_line_after_namespace' => true,
        'constant_case' => true,
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

return $config;

