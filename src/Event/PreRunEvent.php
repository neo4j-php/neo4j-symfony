<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Event;

use Laudis\Neo4j\Databags\Statement;
use Symfony\Contracts\EventDispatcher\Event;

class PreRunEvent extends Event
{
    public const EVENT_ID = 'neo4j.pre_run';

    public function __construct(
        private readonly ?string $alias,
        private readonly Statement $statement,
        private readonly \DateTimeInterface $time,
        private readonly ?string $scheme
    ) {
    }

    /** @api */
    public function getStatement(): Statement
    {
        return $this->statement;
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
