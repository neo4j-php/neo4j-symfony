<?php

namespace Neo4j\Neo4jBundle\Events;

use Laudis\Neo4j\Databags\Statement;
use Symfony\Contracts\EventDispatcher\Event;

class PreRunEvent extends Event
{
    public const EVENT_ID = 'neo4j.pre_run';

    /**
     * @var iterable<Statement>
     */
    private iterable $statements;

    /**
     * @param iterable<Statement> $statements
     */
    public function __construct(iterable $statements)
    {
        $this->statements = $statements;
    }

    /**
     * @return iterable<Statement>
     */
    public function getStatements(): iterable
    {
        return $this->statements;
    }
}
