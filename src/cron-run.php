<?php
declare(strict_types=1);

namespace PingMonitoringTool;

error_reporting(1);
@ini_set('display_errors', "1");

if (!class_exists('SQLite3')) {
    echo 'SQLite3 extension not support!';
    exit(2);
}

$root_dir = realpath(__DIR__.'/');
define('ROOT', $root_dir);

require_once '../vendor/autoload.php';

try {
    Controller::$debug = true;
    $ping = new Controller();
    $ping->run();
} catch (\Error $er)
{
    printf("Error! %s \n", $er->getMessage());
} catch (\Exception $ex) {
    printf("Error! %s \n", $ex->getMessage());
}

