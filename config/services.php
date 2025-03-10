<?php

use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Basic\Session;
use Laudis\Neo4j\Common\DriverSetupManager;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Contracts\SessionInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\DriverConfiguration;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Formatter\SummarizedResultFormatter;
use Neo4j\Neo4jBundle\Builders\ClientBuilder;
use Neo4j\Neo4jBundle\Decorators\SymfonyClient;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\EventListener\Neo4jProfileListener;
use Neo4j\Neo4jBundle\Factories\ClientFactory;
use Neo4j\Neo4jBundle\Factories\StopwatchEventNameFactory;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->set('neo4j.client_factory', ClientFactory::class);

    $services->set(DriverConfiguration::class, DriverConfiguration::class)
        ->factory([DriverConfiguration::class, 'default']);
    $services->set(SessionConfiguration::class, SessionConfiguration::class);
    $services->set(TransactionConfiguration::class, TransactionConfiguration::class);
    $services->set(ClientBuilder::class, ClientBuilder::class)
        ->autowire();

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

    $services->set(SymfonyDriverFactory::class, SymfonyDriverFactory::class)
        ->arg('$handler', service(EventHandler::class))
        ->arg('$uuidFactory', service('uuid.factory')->nullOnInvalid());

    $services->set(StopwatchEventNameFactory::class, StopwatchEventNameFactory::class);
    $services->set(EventHandler::class, EventHandler::class)
        ->arg('$dispatcher', service('event_dispatcher')->nullOnInvalid())
        ->arg('$stopwatch', service('debug.stopwatch')->nullOnInvalid())
        ->arg('$nameFactory', service(StopwatchEventNameFactory::class));

    $services->set(StopwatchEventNameFactory::class);

    $services->set(DriverSetupManager::class, DriverSetupManager::class)
        ->arg('$formatter', service(SummarizedResultFormatter::class))
        ->arg('$configuration', service(DriverConfiguration::class));
    $services->set(SummarizedResultFormatter::class, SummarizedResultFormatter::class)
        ->factory([SummarizedResultFormatter::class, 'create']);

    $services->alias(ClientInterface::class, 'neo4j.client');
    $services->alias(DriverInterface::class, 'neo4j.driver');
    $services->alias(SessionInterface::class, 'neo4j.session');
    $services->alias(TransactionInterface::class, 'neo4j.transaction');

    $services->set('neo4j.subscriber', Neo4jProfileListener::class)
        ->tag('kernel.event_subscriber');
};
