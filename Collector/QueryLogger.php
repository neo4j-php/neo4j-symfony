<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Collector;

use Countable;
use Exception;
use function iterator_to_array;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Exception\Neo4jException;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;

/**
 * @author Xavier Coureau <xavier@pandawan-technology.com>
 *
 * @psalm-import-type OGMTypes from \Laudis\Neo4j\Formatter\OGMFormatter
 *
 * @psalm-type StatementInfo = array{
 *     start_time: float,
 *     query: string,
 *     parameters: string,
 *     end_time: float,
 *     nb_results: int,
 *     statistics: array,
 *     scheme: string,
 *     success: bool,
 *     exceptionCode: string,
 *     exceptionMessage: string
 * }
 */
class QueryLogger implements Countable
{
    private int $nbQueries = 0;

    /**
     * @var array<StatementInfo>
     */
    private array $statements = [];

    public function record(Statement $statement): void
    {
        $statementText = $statement->getText();
        $statementParams = json_encode($statement->getParameters(), JSON_THROW_ON_ERROR);

        $this->statements[] = [
            'start_time' => microtime(true) * 1000,
            'query' => $statementText,
            'parameters' => $statementParams,

            // Add dummy data in case we never run logException or finish
            'end_time' => microtime(true) * 1000, // same
            'nb_results' => 0,
            'statistics' => [],
            'scheme' => '',
            'success' => false,
            'exceptionCode' => '',
            'exceptionMessage' => '',
        ];
    }

    /**
     * @param SummarizedResult<CypherList<CypherMap<OGMTypes>>> $result
     *
     * @throws Exception
     */
    public function finish(SummarizedResult $result): void
    {
        $id = count($this->statements) - 1;

        $summary = $result->getSummary();
        $this->statements[$id] = array_merge($this->statements[$id], [
            'end_time' => $summary->getResultConsumedAfter(),
            'nb_results' => $result->count(),
            'statistics' => iterator_to_array($summary->getCounters()->getIterator(), true),
            'scheme' => $summary->getServerInfo()->getAddress()->getScheme(),
            'success' => true,
        ]);
    }

    public function reset(): void
    {
        $this->statements = [];
    }

    public function logException(Neo4jException $exception): void
    {
        $classification = explode('.', $exception->getNeo4jCode())[1] ?? '';
        $idx = count($this->statements) - 1;
        $this->statements[$idx] = array_merge($this->statements[$idx], [
            'end_time' => microtime(true) * 1000,
            'exceptionCode' => $classification,
            'exceptionMessage' => $exception->getErrors()[0]->getMessage(),
            'success' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->nbQueries;
    }

    /**
     * @return array<StatementInfo>
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    public function getElapsedTime(): float
    {
        $time = 0;

        foreach ($this->statements as $statement) {
            $time += $statement['end_time'] - $statement['start_time'];
        }

        return $time;
    }
}
