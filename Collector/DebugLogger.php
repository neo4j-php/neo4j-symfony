<?php

declare (strict_types=1);

namespace Neo4jCommunity\Neo4jBundle\Collector;

use GraphAware\Common\Cypher\StatementInterface;
use GraphAware\Common\Result\Result;
use GraphAware\Neo4j\Client\Exception\Neo4jExceptionInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class DebugLogger
{
    /**
     * @var array
     */
    private $statement = [];

    /**
     * @var array
     */
    private $result = [];

    /**
     * @var array
     */
    private $exception = [];

    /**
     * @var int
     */
    private $currentStatementId = 0;

    /**
     * @param StatementInterface $statement
     */
    public function addStatement(StatementInterface $statement)
    {
        $this->statement[++$this->currentStatementId] = $statement;
    }

    /**
     * @param Result $result
     */
    public function addResult(Result $result)
    {
        $this->result[$this->currentStatementId] = $result;
    }

    /**
     * @param Neo4jExceptionInterface $exception
     */
    public function addException(Neo4jExceptionInterface $exception)
    {
        $this->exception[$this->currentStatementId] = $exception;
    }

    /**
     * @return StatementInterface[]
     */
    public function getStatements(): array
    {
        return $this->statement;
    }

    /**
     * @return Result[]
     */
    public function getResults(): array
    {
        return $this->result;
    }

    /**
     * @return Neo4jExceptionInterface[]
     */
    public function getExceptions(): array
    {
        return $this->exception;
    }
}
