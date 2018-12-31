#!/usr/bin/env php
<?php
$ini_file = realpath(__DIR__ . "/../versions.ini");
$ini_array = parse_ini_file($ini_file, true);
$build_args  ='';

foreach ($ini_array[$argv[1]] as $key => $value){
    $build_args .= "--build-arg $key=$value ";
}
print $build_args;