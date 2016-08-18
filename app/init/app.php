<?php

use YourNamespace\Init\YourApp;

require __DIR__ . '/../vendor/autoload.php';

$appName = "xxx-app";
$rootPath = realpath(__DIR__.'/../');

return new YourApp($appName, $rootPath);