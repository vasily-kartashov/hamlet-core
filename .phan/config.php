<?php

return [
    'target_php_version' => 7.0,
    'directory_list' => [
        'src/',
        'vendor/'
    ],
    'exclude_analysis_directory_list' => [
        'vendor/'
    ],
    'plugins' => [
        'AlwaysReturnPlugin',
        'UnreachableCodePlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin'
    ]
];
