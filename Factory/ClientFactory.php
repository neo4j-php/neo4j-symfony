<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Factory;

use Laudis\Neo4j\ClientBuilder;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\SymfonyClient;
use Laudis\Neo4j\Contracts\ClientInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class ClientFactory
{
    /**
     * Build a Client form multiple connection.
     */
    public function create(array $names, array $configs, ?EventDispatcherInterface $dispatcher): ClientInterface
    {
        $builder = ClientBuilder::create();
        foreach ($names as $name) {
            $builder = $builder->withDriver($name, $this->getUrl($configs[$name]));
        }

        $client = $builder->withDefaultDriver(reset($names))->build();

        return new SymfonyClient($client, new EventHandler(null));
    }

    /**
     * Get URL form config.
     */
    private function getUrl(array $config): string
    {
        if (null !== $config['dsn']) {
            return $config['dsn'];
        }

        if (isset($config['username'], $config['password'])) {
            return sprintf(
                '%s://%s:%s@%s:%d',
                $config['scheme'] ?? 'bolt',
                $config['username'],
                $config['password'],
                $config['host'],
                $this->getPort($config)
            );
        }

        return sprintf(
            '%s://%s:%d',
            $config['scheme'] ?? 'bolt',
            $config['host'],
            $this->getPort($config)
        );
    }

    private function getPort(array $config): int
    {
        if (isset($config['port'])) {
            return (int) $config['port'];
        }

        return 'http' === $config['scheme'] ? 7474 : 7687;
    }
}
