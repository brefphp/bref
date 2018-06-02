<?php

$stderr = fopen('php://stderr', 'w+');
fwrite($stderr, 'Hello world!');
fclose($stderr);
