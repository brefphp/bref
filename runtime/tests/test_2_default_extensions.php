<?php declare(strict_types=1);

$extensions = [
    'date' => class_exists(\DateTime::class),
    'filter_var' => filter_var('bref@bref.com', FILTER_VALIDATE_EMAIL),
    'hash' => hash('md5', 'Bref') === 'df4647d91c4a054af655c8eea2bce541',
    'libxml' => class_exists(\libXMLError::class),
    'openssl' => strlen(openssl_random_pseudo_bytes(1)) === 1,
    'pntcl' => function_exists('pcntl_fork'),
    'pcre' => preg_match('/abc/', 'abcde', $matches) && $matches[0] === 'abc',
    'readline' => READLINE_LIB === 'libedit',
    'reflection' => class_exists(\ReflectionClass::class),
    'session' => session_status() === PHP_SESSION_NONE,
    'zlib' => md5(gzcompress('abcde')) === 'db245560922b42f1935e73e20b30980e',
];

foreach ($extensions as $extension => $test) {
    if (! $test) {
        throw new Exception($extension . ' extension was not loaded');
    }

    echo "\033[36m [Extension] $extension ✓!\033[0m" . PHP_EOL;
}