<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Event;

use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Exception\Neo4jException;
use Symfony\Contracts\EventDispatcher\Event;

final class FailureEvent extends Event
{
    public const EVENT_ID = 'neo4j.on_failure';

    protected bool $shouldThrowException = true;

    public function __construct(
        public readonly ?string $alias,
        public readonly ?Statement $statement,
        public readonly Neo4jException $exception,
        public readonly \DateTimeInterface $time,
        public readonly ?string $scheme,
        public readonly ?string $transactionId,
    ) {
    }

    /** @api */
    public function disableException(): void
    {
        $this->shouldThrowException = false;
    }

}
