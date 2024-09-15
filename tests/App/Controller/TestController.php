<?php

namespace Neo4j\Neo4jBundle\Tests\App\Controller;

use Laudis\Neo4j\Contracts\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Stopwatch\Stopwatch;

class TestController extends AbstractController
{
    public function __construct(
        private readonly ClientInterface $client
    ) {
    }

    public function __invoke(Profiler $profiler, Stopwatch $stopwatch): Response
    {
        // Successful statement
        $this->client->run('MATCH (n {foo: $bar}) RETURN n', ['bar' => 'baz']);
        try {
            // Failing statement
            $this->client->run('MATCH (n) {x: $x}', ['x' => 1]);
        } catch (\Exception) {
            // ignore
        }

        return $this->render('index.html.twig');
    }
}
