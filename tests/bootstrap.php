<?php

declare(strict_types=1);

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    if (E_USER_DEPRECATED === $errno && (
        str_contains($errstr, 'The "wdt.xml" routing configuration file is deprecated')
        || str_contains($errstr, 'The "profiler.xml" routing configuration file is deprecated')
        || str_contains($errstr, 'Setting the "framework.profiler.collect_serializer_data" config option to "false" is deprecated')
    )) {
        return true;
    }

    return false;
}, E_USER_DEPRECATED);

require_once __DIR__.'/../vendor/autoload.php';
