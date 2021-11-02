<?php

namespace Neo4j\Neo4jBundle;

use Laudis\Neo4j\Common\TransactionHelper;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Contracts\UnmanagedTransactionInterface;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Enum\AccessMode;
use Laudis\Neo4j\Types\CypherList;

class SymfonyClient implements ClientInterface
{
    private ClientInterface $client;
    private EventHandler $handler;

    public function __construct(ClientInterface $client, EventHandler $handler)
    {
        $this->client = $client;
        $this->handler = $handler;
    }

    public function run(string $query, iterable $parameters = [], ?string $alias = null): SummarizedResult
    {
        return $this->runStatement(new Statement($query, $parameters), $alias);
    }

    public function runStatement(Statement $statement, ?string $alias = null): SummarizedResult
    {
        return $this->runStatements([$statement], $alias)->first();
    }

    public function runStatements(iterable $statements, ?string $alias = null): CypherList
    {
        return $this->handler->handle(fn () => $this->client->runStatements($statements, $alias), $statements);
    }

    public function beginTransaction(?iterable $statements = null, ?string $alias = null, ?TransactionConfiguration $config = null): UnmanagedTransactionInterface
    {
        $tsx = new SymfonyTransaction($this->client->beginTransaction(null, $alias, $config), $this->handler);
        $this->handler->handle(fn () => $tsx->runStatements($statements ?? []), $statements ?? []);

        return $tsx;
    }

    public function getDriver(?string $alias): DriverInterface
    {
        return $this->client->getDriver($alias);
    }

    public function writeTransaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfiguration $config = null)
    {
        $sessionConfig = SessionConfiguration::default()->withAccessMode(AccessMode::READ());
        $session = $this->getDriver($alias)->createSession($sessionConfig);

        TransactionHelper::retry(
            fn () => new SymfonyTransaction($session->beginTransaction([], $config), $this->handler),
            $tsxHandler,
            $config ?? TransactionConfiguration::default()
        );
    }

    public function readTransaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfiguration $config = null)
    {
        $sessionConfig = SessionConfiguration::default()->withAccessMode(AccessMode::WRITE());
        $session = $this->getDriver($alias)->createSession($sessionConfig);

        TransactionHelper::retry(
            fn () => new SymfonyTransaction($session->beginTransaction([], $config), $this->handler),
            $tsxHandler,
            $config ?? TransactionConfiguration::default()
        );
    }

    public function transaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfiguration $config = null)
    {
        $this->writeTransaction($tsxHandler, $alias, $config);
    }
}
