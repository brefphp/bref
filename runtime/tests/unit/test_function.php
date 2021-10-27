<?php declare(strict_types=1);

$provider = [
    'bcmath' => bcadd('4', '5') === '9',
    'ctype' => ctype_digit('4'),
    'dom' => class_exists(\DOMDocument::class),
    'exif' => function_exists('exif_imagetype'),
    'fileinfo' => function_exists('finfo_file'),
    'ftp' => function_exists('ftp_connect'),
    'gettext' => gettext('gettext extension') === 'gettext extension',
    'iconv' => iconv_strlen('12characters') === 12,
    'mbstring' => mb_strlen('12characters') === 12,

    # TODO: not working yet
    # 'mysqli' => mysqli_real_escape_string("Bref's extensions") === "Bref\'s extensions",
];

foreach ($provider as $extension => $test) {
    if (! $test) {
        throw new Exception($extension . ' extension was not loaded');
    }
}

echo "\033[36m [Unit] " . count($provider) . " assertions performed!\033[0m" . PHP_EOL;