<?php

declare(strict_types=1);

use Symfony\Bridge\PhpUnit\DeprecationErrorHandler;

DeprecationErrorHandler::register(
    E_ALL & ~E_USER_DEPRECATED & ~E_DEPRECATED,
    [
        'Since symfony/routing 7.3: The "wdt.xml" routing configuration file is deprecated, import "wdt.php" instead.',
        'Since symfony/routing 7.3: The "profiler.xml" routing configuration file is deprecated, import "profiler.php" instead.',
        'Since symfony/framework-bundle 7.3: Setting the "framework.profiler.collect_serializer_data" config option to "false" is deprecated.',
    ]
);

require dirname(__DIR__).'/vendor/autoload.php';
