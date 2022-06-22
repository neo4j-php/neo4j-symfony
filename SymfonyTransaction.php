<?php

namespace Neo4j\Neo4jBundle;

use Laudis\Neo4j\Contracts\UnmanagedTransactionInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;

/**
 * @implements UnmanagedTransactionInterface<SummarizedResult<CypherList<CypherMap<mixed>>>|null>
 */
class SymfonyTransaction implements UnmanagedTransactionInterface
{
    /** @var UnmanagedTransactionInterface<SummarizedResult<CypherList<CypherMap<mixed>>>> */
    private UnmanagedTransactionInterface $tsx;
    private EventHandler $handler;

    /**
     * @param UnmanagedTransactionInterface<SummarizedResult<CypherList<CypherMap<mixed>>>> $tsx
     */
    public function __construct(UnmanagedTransactionInterface $tsx, EventHandler $handler)
    {
        $this->tsx = $tsx;
        $this->handler = $handler;
    }

    public function run(string $statement, iterable $parameters = []): ?SummarizedResult
    {
        return $this->runStatement(new Statement($statement, $parameters));
    }

    public function runStatement(Statement $statement): ?SummarizedResult
    {
        $result = $this->runStatements([$statement]);

        return $result->isEmpty() ? null : $result->first();
    }

    /**
     * @psalm-suppress InvalidReturnStatement
     */
    public function runStatements(iterable $statements): CypherList
    {
        return $this->handler->handle(fn () => $this->tsx->runStatements($statements), $statements);
    }

    /**
     * @psalm-suppress InvalidReturnStatement
     */
    public function commit(iterable $statements = []): CypherList
    {
        return $this->handler->handle(fn () => $this->tsx->commit($statements), $statements);
    }

    public function rollback(): void
    {
        $this->tsx->rollback();
    }

    public function isRolledBack(): bool
    {
        return $this->tsx->isRolledBack();
    }

    public function isCommitted(): bool
    {
        return $this->tsx->isCommitted();
    }

    public function isFinished(): bool
    {
        return $this->tsx->isFinished();
    }
}
