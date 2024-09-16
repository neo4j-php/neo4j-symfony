<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Event;

use Laudis\Neo4j\Databags\ResultSummary;
use Symfony\Contracts\EventDispatcher\Event;

class PostRunEvent extends Event
{
    public const EVENT_ID = 'neo4j.post_run';

    public function __construct(
        private readonly ?string $alias,
        private readonly ResultSummary $result,
        private readonly \DateTimeInterface $time,
        private readonly ?string $scheme,
    ) {
    }

    public function getResult(): ResultSummary
    {
        return $this->result;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }
}
