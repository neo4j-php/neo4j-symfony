<?php

namespace Neo4j\Neo4jBundle\Tests\App\Controller;

use Laudis\Neo4j\Contracts\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class TestController extends AbstractController
{
    public function __construct(
        private readonly ClientInterface $client
    ) {
    }

    public function __invoke(Profiler $profiler): Response
    {
//        dd($profiler->loadProfile('0a1909'));
        // Successful statement
        $this->client->run('MATCH (n) RETURN n');
        try {
            // Failing statement
            $this->client->run('MATCH (n) {x: $x}', ['x' => 1]);
        } catch (\Exception $e) {
            // ignore
        }

        return $this->render('index.html.twig');
    }
}
