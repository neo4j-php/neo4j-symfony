<?php

namespace Neo4j\Neo4jBundle\Event;

class TransactionEvent
{
    public const EVENT_ID = 'neo4j.on_transaction_begin';

    public function __construct(
        public readonly ?string $alias,
        public readonly \DateTimeInterface $time,
        public readonly ?string $scheme,
        public readonly string $transactionId,
    ) {
    }
}
