<?php

namespace Neo4j\Neo4jBundle\Tests\App\Controller;

use Laudis\Neo4j\Contracts\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TestController extends AbstractController
{
    public function __construct(
        private readonly ClientInterface $client
    ) {
    }

    public function __invoke(): Response
    {
        $this->client->run('MATCH (n) RETURN n');
        $this->client->run('MATCH (n) RETURN n');

        return $this->render('index.twig.html');
    }
}
