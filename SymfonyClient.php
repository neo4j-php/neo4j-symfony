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
use Laudis\Neo4j\Types\CypherMap;

/**
 * @implements ClientInterface<SummarizedResult<CypherList<CypherMap<mixed>>>|null>
 */
class SymfonyClient implements ClientInterface
{
    /** @var ClientInterface<SummarizedResult<CypherList<CypherMap<mixed>>>> */
    private ClientInterface $client;
    private EventHandler $handler;

    /**
     * @param ClientInterface<SummarizedResult<CypherList<CypherMap<mixed>>>> $client
     */
    public function __construct(ClientInterface $client, EventHandler $handler)
    {
        $this->client = $client;
        $this->handler = $handler;
    }

    public function run(string $statement, iterable $parameters = [], ?string $alias = null): ?SummarizedResult
    {
        return $this->runStatement(new Statement($statement, $parameters), $alias);
    }

    public function runStatement(Statement $statement, ?string $alias = null): ?SummarizedResult
    {
        $tbr = $this->runStatements([$statement], $alias);

        return $tbr->isEmpty() ? null : $tbr->first();
    }

    /**
     * @psalm-suppress InvalidReturnStatement
     */
    public function runStatements(iterable $statements, ?string $alias = null): CypherList
    {
        return $this->handler->handle(fn () => $this->client->runStatements($statements, $alias), $statements);
    }

    public function beginTransaction(?iterable $statements = null, ?string $alias = null, ?TransactionConfiguration $config = null): UnmanagedTransactionInterface
    {
        $tsx = new SymfonyTransaction($this->client->beginTransaction(null, $alias, $config), $this->handler);
        /**
         * @var callable():CypherList<SummarizedResult<CypherList<CypherMap<mixed>>>> $runHandler
         */
        $runHandler = fn (): CypherList => $tsx->runStatements($statements ?? []);
        $this->handler->handle($runHandler, $statements ?? []);

        return $tsx;
    }

    /**
     * @psalm-mutation-free
     * @psalm-suppress InvalidReturnStatement
     */
    public function getDriver(?string $alias): DriverInterface
    {
        return $this->client->getDriver($alias);
    }

    public function writeTransaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfiguration $config = null)
    {
        $sessionConfig = SessionConfiguration::default()->withAccessMode(AccessMode::READ());
        $session = $this->client->getDriver($alias)->createSession($sessionConfig);

        return TransactionHelper::retry(
            fn () => new SymfonyTransaction($session->beginTransaction([], $config), $this->handler),
            $tsxHandler
        );
    }

    public function readTransaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfiguration $config = null)
    {
        $sessionConfig = SessionConfiguration::default()->withAccessMode(AccessMode::WRITE());
        $session = $this->client->getDriver($alias)->createSession($sessionConfig);

        return TransactionHelper::retry(
            fn () => new SymfonyTransaction($session->beginTransaction([], $config), $this->handler),
            $tsxHandler
        );
    }

    public function transaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfiguration $config = null)
    {
        return $this->writeTransaction($tsxHandler, $alias, $config);
    }

    public function verifyConnectivity(?string $driver = null): bool
    {
        return $this->client->verifyConnectivity($driver);
    }
}
