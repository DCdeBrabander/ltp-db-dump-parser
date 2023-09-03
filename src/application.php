#!/usr/bin/env php
<?php
// application.php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use App\Command\ParseDumpCommand;

$application = new Application();

// ... register commands
$application->add(new ParseDumpCommand());

$application->run();
