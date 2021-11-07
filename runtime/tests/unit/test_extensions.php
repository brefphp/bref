<?php declare(strict_types=1);

$provider = [
    'bcmath' => bcadd('4', '5') === '9',
    'ctype' => ctype_digit('4'),
    'date' => class_exists(\DateTime::class),
    'dom' => class_exists(\DOMDocument::class),
    'exif' => function_exists('exif_imagetype'),
    'fileinfo' => function_exists('finfo_file'),
    'ftp' => function_exists('ftp_connect'),
    'gettext' => gettext('gettext extension') === 'gettext extension',
    'iconv' => iconv_strlen('12characters') === 12,
    'mbstring' => mb_strlen('12characters') === 12,
    'mysqli' => function_exists('mysqli_connect'),
    'opcache' => ini_get('opcache.enable') == 1 && ini_get('opcache.enable_cli') == 1,
    'openssl' => strlen(openssl_random_pseudo_bytes(1)) === 1,
    'pdo' => class_exists(\PDO::class),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'readline' => READLINE_LIB === 'libedit',
    'reflection' => class_exists(\ReflectionClass::class),
    'session' => session_status() === PHP_SESSION_NONE,
    'simplexml' => class_exists(\SimpleXMLElement::class),
    'sodium' => defined('PASSWORD_ARGON2I'),
];

foreach ($provider as $extension => $test) {
    if (! $test) {
        throw new Exception($extension . ' extension was not loaded');
    }
}

echo "\033[36m [Unit] " . count($provider) . " assertions performed!\033[0m" . PHP_EOL;