<?php

namespace Neo4j\Neo4jBundle\Event\Transaction;

final class PostTransactionCommitEvent
{
    public const EVENT_ID = 'neo4j.transaction.commit.post';

    public function __construct(
        public readonly string $alias,
        public readonly \DateTimeInterface $time,
        public readonly string $scheme,
        public readonly string $transactionId,
    ) {
    }
}
