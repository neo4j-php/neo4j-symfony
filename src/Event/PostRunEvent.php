<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Event;

use Laudis\Neo4j\Databags\ResultSummary;
use Symfony\Contracts\EventDispatcher\Event;

class PostRunEvent extends Event
{
    public const EVENT_ID = 'neo4j.post_run';

    public function __construct(
        private string|null $alias,
        private ResultSummary $result
    ) {
    }

    public function getResult(): ResultSummary
    {
        return $this->result;
    }

    public function getAlias(): string|null
    {
        return $this->alias;
    }
}
