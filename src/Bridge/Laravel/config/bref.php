<?php
return [
    'packaging' => [
        'ignore' => [
            // Directories & Fully Qualified Paths
            base_path('vendor'),
            base_path('tests'),
            base_path('storage'),
            base_path('.idea'),
            base_path('.git'),
            // Files Names
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
        'executables' => [
            'artisan'
        ]

    ]
];
