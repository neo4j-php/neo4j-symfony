<?php

namespace Neo4j\Neo4jBundle\Events;

use Laudis\Neo4j\Exception\Neo4jException;
use Symfony\Contracts\EventDispatcher\Event;

class FailureEvent extends Event
{
    public const EVENT_ID = 'neo4j.on_failure';

    protected Neo4jException $exception;

    protected bool $shouldThrowException = true;

    public function __construct(Neo4jException $exception)
    {
        $this->exception = $exception;
    }

    public function getException(): Neo4jException
    {
        return $this->exception;
    }

    public function disableException(): void
    {
        $this->shouldThrowException = false;
    }

    public function shouldThrowException(): bool
    {
        return $this->shouldThrowException;
    }
}
