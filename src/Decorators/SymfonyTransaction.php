<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Decorators;

use Laudis\Neo4j\Contracts\UnmanagedTransactionInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Enum\TransactionState;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Neo4j\Neo4jBundle\EventHandler;
use Override;

final class SymfonyTransaction implements UnmanagedTransactionInterface
{
    /**
     * @param UnmanagedTransactionInterface $tsx
     */
    public function __construct(
        private readonly UnmanagedTransactionInterface $tsx,
        private readonly EventHandler $handler,
        private readonly string $alias,
        private readonly string $scheme,
        private readonly string $transactionId,
    ) {
    }

    #[Override]
    public function run(string $statement, iterable $parameters = []): SummarizedResult
    {
        return $this->runStatement(new Statement($statement, $parameters));
    }

    #[Override]
    public function runStatement(Statement $statement): SummarizedResult
    {
        return $this->handler->handleQuery(fn (Statement $statement) => $this->tsx->runStatement($statement),
            $statement,
            $this->alias,
            $this->scheme,
            $this->transactionId
        );
    }

    /**
     * @psalm-suppress InvalidReturnStatement
     */
    #[Override]
    public function runStatements(iterable $statements): CypherList
    {
        $tbr = [];
        foreach ($statements as $statement) {
            $tbr[] = $this->runStatement($statement);
        }

        return CypherList::fromIterable($tbr);
    }

    #[Override]
    public function commit(iterable $statements = []): CypherList
    {
        $results = $this->runStatements($statements);

        $this->handler->handleTransactionAction(
            TransactionState::COMMITTED,
            $this->transactionId,
            fn () => $this->tsx->commit(),
            $this->alias,
            $this->scheme,
        );

        return $results;
    }

    #[Override]
    public function rollback(): void
    {
        $this->handler->handleTransactionAction(
            TransactionState::ROLLED_BACK,
            $this->transactionId,
            fn () => $this->tsx->commit(),
            $this->alias,
            $this->scheme,
        );
    }

    #[Override]
    public function isRolledBack(): bool
    {
        return $this->tsx->isRolledBack();
    }

    #[Override]
    public function isCommitted(): bool
    {
        return $this->tsx->isCommitted();
    }

    #[Override]
    public function isFinished(): bool
    {
        return $this->tsx->isFinished();
    }
}
