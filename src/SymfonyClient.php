<?php

declare(strict_types=1);

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
 * @implements ClientInterface<SummarizedResult<CypherMap>>
 */
class SymfonyClient implements ClientInterface
{
    /**
     * @param ClientInterface<SummarizedResult<CypherMap>> $client
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly EventHandler $handler,
    ) {
    }

    public function run(string $statement, iterable $parameters = [], ?string $alias = null): ?SummarizedResult
    {
        return $this->runStatement(new Statement($statement, $parameters), $alias);
    }

    public function runStatement(Statement $statement, ?string $alias = null): ?SummarizedResult
    {
        return $this->handler->handle(
            fn (Statement $statement) => $this->client->runStatement($statement, $alias),
            $statement,
            $alias,
            null
        );
    }

    public function runStatements(iterable $statements, ?string $alias = null): CypherList
    {
        $tbr = [];
        foreach ($statements as $statement) {
            $tbr[] = $this->runStatement($statement, $alias);
        }

        return CypherList::fromIterable($tbr);
    }

    public function beginTransaction(
        ?iterable $statements = null,
        ?string $alias = null,
        ?TransactionConfiguration $config = null,
    ): UnmanagedTransactionInterface {
        $tsx = new SymfonyTransaction($this->client->beginTransaction(null, $alias, $config), $this->handler, $alias);

        $runHandler = fn (Statement $statement): CypherList => $tsx->runStatement($statement);

        foreach (($statements ?? []) as $statement) {
            $this->handler->handle($runHandler, $statement, $alias, null);
        }

        return $tsx;
    }

    public function getDriver(?string $alias): DriverInterface
    {
        return $this->client->getDriver($alias);
    }

    public function writeTransaction(
        callable $tsxHandler,
        ?string $alias = null,
        ?TransactionConfiguration $config = null,
    ) {
        $sessionConfig = SessionConfiguration::default()->withAccessMode(AccessMode::READ());
        $session = $this->client->getDriver($alias)->createSession($sessionConfig);

        return TransactionHelper::retry(
            fn () => new SymfonyTransaction($session->beginTransaction([], $config), $this->handler, $alias),
            $tsxHandler
        );
    }

    public function readTransaction(
        callable $tsxHandler,
        ?string $alias = null,
        ?TransactionConfiguration $config = null,
    ) {
        $sessionConfig = SessionConfiguration::default()->withAccessMode(AccessMode::WRITE());
        $session = $this->client->getDriver($alias)->createSession($sessionConfig);

        return TransactionHelper::retry(
            fn () => new SymfonyTransaction($session->beginTransaction([], $config), $this->handler, $alias),
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

    public function bindTransaction(?string $alias = null, ?TransactionConfiguration $config = null): void
    {
        $this->client->bindTransaction($alias, $config);
    }

    public function commitBoundTransaction(?string $alias = null, int $depth = 1): void
    {
        $this->client->commitBoundTransaction($alias, $depth);
    }

    public function rollbackBoundTransaction(?string $alias = null, int $depth = 1): void
    {
        $this->client->rollbackBoundTransaction($alias, $depth);
    }

    public function hasDriver(string $alias): bool
    {
        return $this->client->hasDriver($alias);
    }
}
