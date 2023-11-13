<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['vendor', 'lib/components', 'foam', 'ample', 'client', 'newclient'])
    ->in('public/')
    ->in('src/')
    ->in('tests/')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        'binary_operator_spaces' => [
            'operators' => ['=' => 'align']
        ],
        'blank_line_after_namespace' => true,
        'blank_line_before_statement' => [
            'statements' => ['declare', 'return']
        ],
        'concat_space' => [
            'spacing' => 'one'
        ],
        'curly_braces_position' => [
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'allow_single_line_empty_anonymous_classes' => true,
            'allow_single_line_anonymous_functions' => true
        ],
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

