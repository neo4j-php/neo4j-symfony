<?php

namespace Neo4j\Neo4jBundle\Factories;

use Laudis\Neo4j\Enum\TransactionState;

class StopwatchEventNameFactory
{
    public function __construct(
    ) {
    }

    public function createQueryEventName(string $alias, ?string $transactionId): string
    {
        if (null === $transactionId) {
            return sprintf('neo4j.%s.query', $alias);
        }

        return sprintf('neo4j.%s.transaction.%s.query', $alias, $transactionId);
    }

    public function createTransactionEventName(string $alias, string $transactionId, TransactionState $nextTransactionState): string
    {
        return sprintf(
            'neo4j.%s.transaction.%s.%s',
            $alias,
            $transactionId,
            $this->nextTransactionStateToAction($nextTransactionState)
        );
    }

    private function nextTransactionStateToAction(TransactionState $state): string
    {
        return match ($state) {
            TransactionState::COMMITTED => 'commit',
            TransactionState::ACTIVE => 'begin',
            TransactionState::ROLLED_BACK => 'rollback',
            TransactionState::TERMINATED => 'error',
        };
    }
}
