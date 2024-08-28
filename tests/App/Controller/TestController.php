<?php

namespace Neo4j\Neo4jBundle\Tests\App\Controller;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Neo4j\Neo4jDriver;
use Neo4j\Neo4jBundle\SymfonyClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    public function __construct(
        private readonly ClientInterface $client
    ) {
    }

    #[Route("/", methods: ["GET"])]
    public function index(): Response
    {
        $this->client->run('MATCH (n) RETURN n');
        $this->client->run('MATCH (n) RETURN n');
        return $this->render('index.twig.html');
    }
}