<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['vendor', 'lib/components', 'foam', 'ample', 'client', 'newclient'])
    ->in('./')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR2' => true,
        'binary_operator_spaces' => ['operators' => ['=' => 'align']],
        'blank_line_after_namespace' => true,
        'blank_line_before_statement' => ['statements' => ['declare', 'return']],
        'braces' => ['allow_single_line_closure' => true],
        'concat_space' => ['spacing' => 'one'],
        'constant_case' => true,
        'elseif' => true,
        'encoding' => true,
        'full_opening_tag' => true,
        'line_ending' => true,
        'method_argument_space' => false,
        'no_break_comment' => false,
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
        'strict_param' => false,
        'visibility_required' => false,
     ])
   ->setIndent("    ")
   ->setUsingCache(false)
   ->setFinder($finder)
   ->setLineEnding("\n")
;

