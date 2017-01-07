<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Collector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * @author Xavier Coureau <xavier@pandawan-technology.com>
 */
final class Neo4jDataCollector extends DataCollector
{
    /**
     * @var QueryLogger
     */
    private $queryLogger;

    public function __construct(QueryLogger $logger)
    {
        $this->queryLogger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['nb_queries'] = count($this->queryLogger);
        $this->data['statements'] = $this->queryLogger->getStatements();
        $this->data['time'] = $this->queryLogger->getElapsedTime();
    }

    /**
     * @return int
     */
    public function getQueryCount()
    {
        return $this->data['nb_queries'];
    }

    /**
     * @return QueryLogger
     */
    public function getStatements()
    {
        return $this->data['statements'];
    }

    /**
     * @return float
     */
    public function getTime()
    {
        return $this->data['time'];
    }

    /**
     * @return float
     */
    public function getTimeForQuery()
    {
        return $this->data['time'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'neo4j';
    }
}
