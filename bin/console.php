<?php

use Neo4j\Neo4jBundle\Tests\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$console = new Application(new TestKernel($_ENV['APP_ENV'] ?? 'dev', true));

$console->run();