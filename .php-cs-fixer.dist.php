<?php
declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(['src', 'tests']);

// https://mlocati.github.io/php-cs-fixer-configurator/
return (new PhpCsFixer\Config())
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setRules([
        '@PSR12' => true,

        'align_multiline_comment' => [
            'comment_type' => 'phpdocs_only',
        ],

        'array_syntax' => [
            'syntax' => 'short',
        ],

        'binary_operator_spaces' => [
            'default'   => 'single_space',
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ],
        ],

        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'continue',
                'do',
                'exit',
                'goto',
                'if',
                'return',
                'switch',
                'throw',
                'try',
                'while',
                'yield',
            ],
        ],

        'blank_line_after_opening_tag' => false,
        'linebreak_after_opening_tag'  => true,
        'cast_spaces'                  => true,

        'class_attributes_separation' => [
            'elements' => [
                'const'    => 'one',
                'method'   => 'one',
                'property' => 'one',
            ],
        ],

        'class_definition' => [
            'multi_line_extends_each_single_line' => false,
            'single_item_single_line'             => false,
            'single_line'                         => false,
        ],

        'class_keyword_remove'       => false,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'compact_nullable_typehint'  => true,

        'concat_space' => [
            'spacing' => 'one',
        ],

        'constant_case'           => [
            'case' => 'lower',
        ],
        'declare_equal_normalize' => [
            'space' => 'none',
        ],

        'declare_strict_types' => true,
        'dir_constant'         => true,
        'elseif'               => true,
        'encoding'             => true,

        'escape_implicit_backslashes' => [
            'double_quoted'  => true,
            'heredoc_syntax' => true,
            'single_quoted'  => false,
        ],

        'explicit_indirect_variable'   => true,
        'full_opening_tag'             => true,
        'fully_qualified_strict_types' => true,

        'function_declaration' => [
            'closure_function_spacing' => 'one',
        ],

        'function_to_constant'    => true,
        'function_typehint_space' => true,
        'global_namespace_import' => [
            'import_classes'   => null,
            'import_constants' => null,
            'import_functions' => null,
        ],
        'include'                 => true,

        'increment_style' => [
            'style' => 'pre',
        ],

        'indentation_type' => true,

        'line_ending'           => true,
        'lowercase_cast'        => true,
        'lowercase_keywords'    => true,
        'magic_constant_casing' => true,

        'method_argument_space' => [
            'on_multiline'                     => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],

        'method_chaining_indentation'            => true,
        'modernize_types_casting'                => true,
        'multiline_whitespace_before_semicolons' => true,
        'native_function_casing'                 => true,
        'native_function_invocation'             => true,
        'new_with_braces'                        => true,
        'no_alias_functions'                     => true,
        'no_blank_lines_after_class_opening'     => false,
        'no_blank_lines_after_phpdoc'            => true,
        'no_closing_tag'                         => true,
        'no_empty_phpdoc'                        => true,
        'no_empty_statement'                     => true,

        'no_extra_blank_lines' => [
            'tokens' => [
                'continue',
                'curly_brace_block',
                'return',
            ],
        ],

        'no_leading_import_slash'         => true,
        'no_leading_namespace_whitespace' => true,

        'no_mixed_echo_print' => [
            'use' => 'echo',
        ],

        'no_multiline_whitespace_around_double_arrow'      => true,
        'no_null_property_initialization'                  => true,
        'no_short_bool_cast'                               => true,
        'no_singleline_whitespace_before_semicolons'       => true,
        'no_spaces_after_function_name'                    => true,
        'no_spaces_around_offset'                          => true,
        'no_spaces_inside_parenthesis'                     => true,
        'no_superfluous_elseif'                            => true,
        'no_superfluous_phpdoc_tags'                       => [
            'allow_mixed'         => true,
            'allow_unused_params' => true,
            'remove_inheritdoc'   => true,
        ],
        'no_trailing_whitespace'                           => true,
        'no_trailing_whitespace_in_comment'                => true,
        'no_unneeded_control_parentheses'                  => true,
        'no_unneeded_curly_braces'                         => true,
        'no_unneeded_final_method'                         => true,
        'no_unused_imports'                                => true,
        'no_useless_else'                                  => true,
        'no_useless_return'                                => true,
        'no_whitespace_before_comma_in_array'              => true,
        'no_whitespace_in_blank_line'                      => true,
        'not_operator_with_space'                          => false,
        'not_operator_with_successor_space'                => false,
        'nullable_type_declaration_for_default_null_value' => [
            'use_nullable_type_declaration' => true,
        ],
        'object_operator_without_whitespace'               => true,

        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],

        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order'  => [
                'const',
                'class',
                'function',
            ],
        ],

        'php_unit_fqcn_annotation' => true,

        'phpdoc_add_missing_param_annotation' => [
            'only_untyped' => true,
        ],

        'phpdoc_align'     => true,
        'phpdoc_indent'    => true,
        'phpdoc_line_span' => [
            'const'    => 'multi',
            'method'   => 'multi',
            'property' => 'multi',
        ],

        'phpdoc_no_access'                              => true,
        'phpdoc_no_empty_return'                        => true,
        'phpdoc_no_useless_inheritdoc'                  => true,
        'phpdoc_no_package'                             => true,
        'phpdoc_order'                                  => true,
        'phpdoc_return_self_reference'                  => [
            'replacements' => [
                'this' => 'self',
            ],
        ],
        'phpdoc_scalar'                                 => true,
        'phpdoc_separation'                             => true,
        'phpdoc_single_line_var_spacing'                => true,
        'phpdoc_summary'                                => true,
        'phpdoc_to_comment'                             => false,  // Set to false because it affects @noinspection
        'phpdoc_trim'                                   => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types'                                  => true,

        'phpdoc_types_order' => [
            'sort_algorithm'  => 'alpha',
            'null_adjustment' => 'always_last',
        ],

        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name'             => true,

        'return_type_declaration' => [
            'space_before' => 'none',
        ],

        'self_accessor'                      => true,
        'self_static_accessor'               => true,
        'semicolon_after_instruction'        => true,
        'short_scalar_cast'                  => true,
        'simplified_null_return'             => true,
        'single_blank_line_at_eof'           => true,
        'single_blank_line_before_namespace' => true,
        'single_class_element_per_statement' => true,
        'single_import_per_statement'        => true,
        'single_line_after_imports'          => true,
        'single_line_comment_style'          => true,
        'single_quote'                       => true,

        'space_after_semicolon' => [
            'remove_in_empty_for_expressions' => true,
        ],

        'standardize_not_equals'         => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space'              => true,
        'ternary_operator_spaces'        => true,
        'ternary_to_null_coalescing'     => true,
        'trailing_comma_in_multiline'    => true,
        'trim_array_spaces'              => true,
        'unary_operator_spaces'          => true,

        'visibility_required' => [
            'elements' => [
                'property',
                'method',
                'const',
            ],
        ],

        'void_return' => true,

        'whitespace_after_comma_in_array' => true,

        'yoda_style' => [
            'equal'            => true,
            'identical'        => true,
            'less_and_greater' => null,
        ],
    ]);
