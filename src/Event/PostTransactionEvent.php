<?php

namespace Neo4j\Neo4jBundle\Event;

use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\Statement;

class PostTransactionEvent
{
    public const EVENT_ID = 'neo4j.on_transaction_end';

    public function __construct(
        public readonly ?string $alias,
        public readonly Statement $statement,
        public readonly \DateTimeInterface $time,
        public readonly ?string $scheme,
        private readonly TransactionInterface $transaction,
    ) {
    }

    public function getTransactionId(): int
    {
        return spl_object_id($this->transaction);
    }
}
