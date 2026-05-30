<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['vendor', 'lib/components', 'foam', 'ample', 'client', 'newclient'])
    ->in('public/')
    ->in('src/')
    ->in('tests/')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'simplified_if_return' => true,
        'no_superfluous_phpdoc_tags' => true,
        'single_class_element_per_statement' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one']],
        'no_superfluous_phpdoc_tags' => true,
        'single_class_element_per_statement' => true,
        'array_syntax' => [
            'syntax' => 'short'
        ],
        'binary_operator_spaces' => [
            'operators' => ['=' => 'align']
        ],
        'blank_line_after_namespace' => true,
        'blank_line_before_statement' => [
            'statements' => ['declare', 'return']
        ],
        'blank_line_between_import_groups' => true,
        'concat_space' => [
            'spacing' => 'one'
        ],
        'constant_case' => true,
        'braces_position' => [
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'allow_single_line_empty_anonymous_classes' => true,
            'allow_single_line_anonymous_functions' => true
        ],
        'declare_equal_normalize' => [
            'space' => 'none'
        ],
        'elseif' => true,
        'encoding' => true,
        'full_opening_tag' => true,
        'line_ending' => true,
        'lowercase_cast' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'trailing_comma_in_multiline' => [
            'elements' => ['parameters'],
        ],
        'multiline_comment_opening_closing' => true,
        'no_break_comment' => false,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_in_blank_line' => true,
        'return_type_declaration' => [
            'space_before' => 'none',
        ],
        'single_blank_line_at_eof' => true,
        'strict_param' => false,
        'modifier_keywords' => [
            'elements' => ['const', 'method', 'property']
        ],
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha'
        ]
    ])
    ->setIndent("    ")
    ->setFinder($finder)
    ->setLineEnding("\n")
    ->setUsingCache(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;

