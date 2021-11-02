<?php

namespace Neo4j\Neo4jBundle;

use Laudis\Neo4j\Contracts\UnmanagedTransactionInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;

class SymfonyTransaction implements UnmanagedTransactionInterface
{
    private UnmanagedTransactionInterface $tsx;
    private EventHandler $handler;

    public function __construct(UnmanagedTransactionInterface $tsx, EventHandler $handler)
    {
        $this->tsx = $tsx;
        $this->handler = $handler;
    }

    public function run(string $statement, iterable $parameters = []): SummarizedResult
    {
        return $this->runStatement(new Statement($statement, $parameters));
    }

    public function runStatement(Statement $statement): SummarizedResult
    {
        return $this->runStatements([$statement])->first();
    }

    public function runStatements(iterable $statements): CypherList
    {
        return $this->handler->handle(fn () => $this->tsx->runStatements($statements), $statements);
    }

    public function commit(iterable $statements = []): CypherList
    {
        return $this->handler->handle(fn () => $this->tsx->commit($statements), $statements);
    }

    public function rollback(): void
    {
        $this->tsx->rollback();
    }
}
