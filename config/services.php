<?php

use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Basic\Session;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Contracts\SessionInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Neo4j\Neo4jBundle\ClientFactory;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\EventListener\Neo4jProfileListener;
use Neo4j\Neo4jBundle\SymfonyClient;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->set('neo4j.client_factory', ClientFactory::class)
        ->args([
            service('neo4j.event_handler'),
        ]);

    $services->set('neo4j.client', SymfonyClient::class)
        ->factory([service('neo4j.client_factory'), 'create'])
        ->public();

    $services->set('neo4j.driver', Driver::class)
        ->factory([service('neo4j.client'), 'getDriver'])
        ->public();

    $services->set('neo4j.session', Session::class)
        ->factory([service('neo4j.driver'), 'createSession'])
        ->share(false)
        ->public();

    $services->set('neo4j.transaction', TransactionInterface::class)
        ->factory([service('neo4j.session'), 'beginTransaction'])
        ->share(false)
        ->public();

    $services->alias(ClientInterface::class, 'neo4j.client');
    $services->alias(DriverInterface::class, 'neo4j.driver');
    $services->alias(SessionInterface::class, 'neo4j.session');
    $services->alias(TransactionInterface::class, 'neo4j.transaction');

    $services->set('neo4j.subscriber', Neo4jProfileListener::class)
        ->tag('kernel.event_subscriber');
};
