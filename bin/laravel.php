<?php

use Recca0120\ParallelTest\Application;
use Recca0120\ParallelTest\Console\RequestAsyncCommand;

require_once __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../vendor/orchestra/testbench-core/laravel/bootstrap/app.php';

$application = new Application();
$command = new RequestAsyncCommand($app);
$application->add($command);
$application->setDefaultCommand($command->getName(), true);
$application->run();
