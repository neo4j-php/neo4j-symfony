<?php

namespace Neo4j\Neo4jBundle\Tests\App\Controller;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TestController extends AbstractController
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function runOnClient(ClientInterface $client): Response
    {
        $client->run('MATCH (n {foo: $bar}) RETURN n', ['bar' => 'baz']);
        try {
            $client->run('MATCH (n) {x: $x}', ['x' => 1]);
        } catch (\Exception) {
            $this->logger->warning('Detected failed statement');
        }

        return $this->render('index.html.twig');
    }

    public function runOnSession(SessionInterface $session): Response
    {
        $session->run('MATCH (n {foo: $bar}) RETURN n', ['bar' => 'baz']);
        try {
            $session->run('MATCH (n) {x: $x}', ['x' => 1]);
        } catch (\Exception) {
            $this->logger->warning('Detected failed statement');
        }

        return $this->render('index.html.twig');
    }

    public function runOnTransaction(SessionInterface $session): Response
    {
        $tsx = $session->beginTransaction();

        $tsx->run('MATCH (n {foo: $bar}) RETURN n', ['bar' => 'baz']);
        try {
            $tsx->run('MATCH (n) {x: $x}', ['x' => 1]);
        } catch (\Exception) {
            $this->logger->warning('Detected failed statement');
        } finally {
            $tsx->rollback();
        }

        return $this->render('index.html.twig');
    }
}
