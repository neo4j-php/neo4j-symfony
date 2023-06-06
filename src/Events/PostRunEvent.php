<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Events;

use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Symfony\Contracts\EventDispatcher\Event;

class PostRunEvent extends Event
{
    public const EVENT_ID = 'neo4j.post_run';

    /**
     * @param CypherList<SummarizedResult<CypherMap>> $results
     */
    public function __construct(
        protected CypherList $results
    ) {}

    /**
     * @return CypherList<SummarizedResult<CypherMap>>
     */
    public function getResults(): CypherList
    {
        return $this->results;
    }
}
