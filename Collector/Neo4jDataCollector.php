<?php

declare(strict_types=1);

namespace Neo4j\Bundle\Collector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class Neo4jDataCollector extends DataCollector
{
    private $logger;

    public function __construct(DebugLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    }

    /**
     * @return array|\GraphAware\Common\Cypher\StatementInterface[]
     */
    public function getStatements()
    {
        return $this->logger->getStatements();
    }

    /**
     * @return array|\GraphAware\Common\Result\Result[]
     */
    public function getResults()
    {
        return $this->logger->getResults();
    }

    /**
     * @return array|\GraphAware\Neo4j\Client\Exception\Neo4jExceptionInterface[]
     */
    public function getExceptions()
    {
        return $this->logger->getExceptions();
    }

    /**
     * @param int $idx
     *
     * @return bool
     */
    public function wasSuccessful(int $idx): bool
    {
        return isset($this->logger->getResults()[$idx]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'neo4j';
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->logger);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($data)
    {
        $this->logger = unserialize($data);
    }
}
