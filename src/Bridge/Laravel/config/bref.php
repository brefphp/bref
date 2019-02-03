<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Bref Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your Lambda. This value is used when the
    | framework needs to generate the lambda function names.
    |
    */
    'name' => env('BREF_NAME', env('APP_NAME')),

    'website_name' => env('BREF_NAME', env('APP_NAME')) . '-website-' . env('APP_ENV'),
    'artisan_name' => env('BREF_NAME', env('APP_NAME')) . '-artisan-' . env('APP_ENV'),

    /*
    |--------------------------------------------------------------------------
    |Packaging
    |--------------------------------------------------------------------------
    |
    | This array configures the files that should be ignored when packaging
    | your application, as well as identifying executable files.
    |
    */
    'packaging' => [
        'ignore' => [
            // Directories & Fully Qualified Paths
            base_path('vendor'),
            base_path('tests'),
            base_path('storage'),
            base_path('.idea'),
            base_path('.git'),
            // File Names
            '.gitignore',
            '.env',
            '.env.example',
            '.gitkeep',
            '.htaccess',
            'readme.md',
            'versions.json',
            '.php_cs.cache',
            'composer.json',
            'composer.lock',
            '.DS_Store',
            '.editorconfig',
            '.gitattributes',
            '.stack.yaml',
            'package.json',
            'phpunit.xml',
            'server.php',
            'template.yaml'
        ],
        // Any executables should be here.
        'executables' => [
            'artisan'
        ]
    ]
];
