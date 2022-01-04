<?php declare(strict_types=1);

error_log('This is a log message');

sleep((int) ($_GET['timeout'] ?? 10));
