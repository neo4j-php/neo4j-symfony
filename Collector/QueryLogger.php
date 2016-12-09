<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Collector;

use GraphAware\Common\Cypher\StatementInterface;
use GraphAware\Common\Result\StatementResult as StatementResultInterface;

/**
 * @author Xavier Coureau <xavier@pandawan-technology.com>
 */
class QueryLogger implements \Countable
{
    /**
     * @var int
     */
    private $nbQueries = 0;

    /**
     * @var array
     */
    private $statements = [];

    /**
     * @var array
     */
    private $statementsHash = [];

    /**
     * @param StatementInterface $statement
     */
    public function record(StatementInterface $statement)
    {
        $statementText = $statement->text();
        $statementParams = json_encode($statement->parameters());
        $tag = $statement->getTag() ?: -1;

        if (isset($this->statementsHash[$statementText][$statementParams][$tag])) {
            return;
        }

        $idx = $this->nbQueries++;
        $this->statements[$idx]['start_time'] = microtime(true) * 1000;
        $this->statementsHash[$statementText][$statementParams][$tag] = $idx;
    }

    /**
     * @param StatementResultInterface $statementResult
     */
    public function finish(StatementResultInterface $statementResult)
    {
        $statement = $statementResult->statement();
        $statementText = $statement->text();
        $statementParams = $statement->parameters();
        $encodedParameters = json_encode($statementParams);
        $tag = $statement->getTag() ?: -1;

        if (!isset($this->statementsHash[$statementText][$encodedParameters][$tag])) {
            $idx = $this->nbQueries++;
            $this->statements[$idx]['start_time'] = null;
            $this->statementsHash[$idx] = $idx;
        } else {
            $idx = $this->statementsHash[$statementText][$encodedParameters][$tag];
        }

        $this->statements[$idx] += [
            'end_time' => microtime(true) * 1000,
            'query' => $statementText,
            'parameters' => $statementParams,
            'tag' => $statement->getTag(),
            'nb_results' => $statementResult->size(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->nbQueries;
    }

    /**
     * @return array[]
     */
    public function getStatements()
    {
        return $this->statements;
    }

    /**
     * @return array
     */
    public function getStatementsHash()
    {
        return $this->statementsHash;
    }

    /**
     * @return int
     */
    public function getElapsedTime()
    {
        $time = 0;

        foreach ($this->statements as $statement) {
            if (!isset($statement['start_time'], $statement['end_time'])) {
                continue;
            }

            $time += $statement['end_time'] - $statement['start_time'];
        }

        return $time;
    }
}
