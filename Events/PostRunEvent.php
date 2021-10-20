<?php

namespace Neo4j\Neo4jBundle\Events;

use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Symfony\Contracts\EventDispatcher\Event;

class PostRunEvent extends Event
{
    public const EVENT_ID = 'neo4j.post_run';

    /** @var CypherList<SummarizedResult> */
    protected CypherList $results;

    /**
     * @param CypherList<SummarizedResult> $results
     */
    public function __construct(CypherList $results)
    {
        $this->results = $results;
    }

    /**
     * @return CypherList<SummarizedResult>
     */
    public function getResults(): CypherList
    {
        return $this->results;
    }
}
