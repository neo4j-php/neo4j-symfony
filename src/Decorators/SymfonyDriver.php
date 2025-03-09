<?php

namespace Neo4j\Neo4jBundle\Decorators;

use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;

/**
 * @implements DriverInterface<SummarizedResult<CypherMap>>
 *
 * @psalm-immutable
 *
 * @psalm-suppress ImpureMethodCall
 */
class SymfonyDriver implements DriverInterface
{
    public function __construct(
        private readonly Driver $driver,
        private readonly SymfonyDriverFactory $factory,
        private readonly string $alias,
        private readonly string $schema,
    ) {
    }

    public function createSession(?SessionConfiguration $config = null): SymfonySession
    {
        return $this->factory->createSession($this->driver, $config, $this->alias, $this->schema);
    }

    public function verifyConnectivity(?SessionConfiguration $config = null): bool
    {
        return $this->driver->verifyConnectivity();
    }

    public function closeConnections(): void
    {
        $this->driver->closeConnections();
    }
}
