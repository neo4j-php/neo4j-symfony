<?php

namespace Neo4j\Neo4jBundle\Tests\App\Controller;

use Laudis\Neo4j\Contracts\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TestController extends AbstractController
{
    public function __construct(
        private readonly ClientInterface $client,
    ) {
    }

    public function __invoke(): Response
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
