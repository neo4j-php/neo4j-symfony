<?php

namespace Neo4j\Neo4jBundle\Events;

use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @psalm-import-type OGMResults from \Laudis\Neo4j\Formatter\OGMFormatter
 */
class PostRunEvent extends Event
{
    public const EVENT_ID = 'neo4j.post_run';

    /** @var CypherList<SummarizedResult<OGMResults>> */
    protected CypherList $results;

    /**
     * @param CypherList<SummarizedResult<OGMResults>> $results
     */
    public function __construct(CypherList $results)
    {
        $this->results = $results;
    }

    /**
     * @return CypherList<SummarizedResult<OGMResults>>
     */
    public function getResults(): CypherList
    {
        return $this->results;
    }
}
