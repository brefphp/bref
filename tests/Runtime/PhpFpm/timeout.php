<?php declare(strict_types=1);

if (isset($_GET['timeout'])) {
    sleep((int) $_GET['timeout']);
}
