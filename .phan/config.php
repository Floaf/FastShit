<?php

return [
    // Backwards Compatibility Checking
    'backward_compatibility_checks' => false,

    // Vilka kataloger som ska analyseras
    'directory_list' => [
        'src'
    ],

    // Exkludera mappar och filer
    'exclude_analysis_directory_list' => [
    ],

    'file_suppressions' => [
    ],

    'exclude_file_list' => [
    ],

    'suppress_issue_types' => [
        'PhanPluginRemoveDebugEcho',
        'PhanPossiblyUndeclaredProperty',
        'PhanImpossibleTypeComparison',
        'PhanCoalescingNeverNull'
    ],

    // Ange vilka versioner av PHP som din kod ska vara kompatibel med
    'target_php_version' => '8.4',

    'minimum_severity' => 8, // 0

    'strict_type_checking' => true,
    'strict_method_checking' => true,
    'strict_object_checking' => true,
    'strict_param_checking' => true,
    'strict_property_checking' => true,
    'strict_return_checking' => true,

    'redundant_condition_detection' => true,
    'analyze_signature_compatibility' => true,
    'force_tracking_references' => true,
    'null_casts_as_any_type' => true,
    'simplify_ast' => true,
    'null_casts_as_array' => false,
    'array_casts_as_null' => false,
    'check_docblock_signature_return_type_match' => true,
    'check_docblock_signature_param_type_match' => true,

    // Aktivera eller inaktivera plugins
    'plugins' => [
        'AlwaysReturnPlugin',
        'DollarDollarPlugin',
        'UnreachableCodePlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'UseReturnValuePlugin',
        'DuplicateExpressionPlugin',
        'EmptyStatementListPlugin',
        'LoopVariableReusePlugin',
        'DeprecateAliasPlugin',
        'StaticVariableMisusePlugin',
        'RemoveDebugStatementPlugin',
        'PHPDocRedundantPlugin',
        'ShortArrayPlugin',
        'PHPDocToRealTypesPlugin',
        'PreferNamespaceUsePlugin',
        'RedundantAssignmentPlugin',
        'SimplifyExpressionPlugin',
        'UnsafeCodePlugin',
        'UnknownElementTypePlugin'
    ],

    'color_issue_messages_if_supported' => true,
    'maximum_recursion_depth' => 10
];
