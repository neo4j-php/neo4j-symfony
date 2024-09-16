<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Event;

use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Exception\Neo4jException;
use Symfony\Contracts\EventDispatcher\Event;

class FailureEvent extends Event
{
    public const EVENT_ID = 'neo4j.on_failure';

    protected bool $shouldThrowException = true;

    public function __construct(
        private readonly ?string $alias,
        private readonly Statement $statement,
        private readonly Neo4jException $exception,
        private readonly \DateTimeInterface $time,
        private readonly ?string $scheme
    ) {
    }

    public function getException(): Neo4jException
    {
        return $this->exception;
    }

    /** @api */
    public function disableException(): void
    {
        $this->shouldThrowException = false;
    }

    public function shouldThrowException(): bool
    {
        return $this->shouldThrowException;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getStatement(): Statement
    {
        return $this->statement;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }
}
