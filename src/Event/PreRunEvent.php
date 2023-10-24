<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Event;

use Laudis\Neo4j\Databags\Statement;
use Symfony\Contracts\EventDispatcher\Event;

class PreRunEvent extends Event
{
    public const EVENT_ID = 'neo4j.pre_run';

    public function __construct(private string|null $alias, private Statement $statement)
    {
    }

    public function getStatement(): Statement
    {
        return $this->statement;
    }

    public function getAlias(): string|null
    {
        return $this->alias;
    }
}
