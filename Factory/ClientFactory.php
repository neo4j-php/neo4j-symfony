<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Factory;

use GraphAware\Neo4j\Client\Client;
use GraphAware\Neo4j\Client\ClientInterface;
use GraphAware\Neo4j\Client\Connection\ConnectionManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class ClientFactory
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @param ConnectionManager             $connectionManager
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(ConnectionManager $connectionManager, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->connectionManager = $connectionManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Build an Client form multiple connection.
     *
     * @param string $names
     *
     * @return ClientInterface
     */
    public function create(array $names): ClientInterface
    {
        // Create a new connection manager specific for this client
        $clientConnectionManager = new ConnectionManager();
        foreach ($names as $name) {
            $clientConnectionManager->registerExistingConnection($name, $this->connectionManager->getConnection($name));
        }

        $firstName = reset($names);
        $clientConnectionManager->setMaster($firstName);

        return new Client($clientConnectionManager, $this->eventDispatcher);
    }
}
