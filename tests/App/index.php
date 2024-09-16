<?php

require __DIR__.'/../../vendor/autoload.php';

use Neo4j\Neo4jBundle\Tests\App\TestKernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

Debug::enable();

$kernel = new TestKernel('dev', true);
$kernel->boot();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
if ($kernel->getContainer()->has('profiler')) {
    /** @var Symfony\Component\HttpKernel\Profiler\Profiler $profiler */
    $profiler = $kernel->getContainer()->get('profiler');
    $profile = $profiler->collect($request, $response);
    if (null === $profile) {
        error_log('Profiler token was not generated!!!');
    } else {
        error_log('Profiler token: '.$profile->getToken());
    }
} else {
    error_log('Profiler service not found in container');
}
$response->send();
$kernel->terminate($request, $response);
