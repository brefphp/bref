<?php declare(strict_types=1);

echo 'Response';
fastcgi_finish_request();

// Work after the response
usleep((int) ($_GET['ms'] ?? 300) * 1000);
file_put_contents($_GET['file'], 'Done');
