<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Collector;

use Neo4j\Neo4jBundle\EventListener\Neo4jProfileListener;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @var array{
 *     successful_statements_count: int,
 *     failed_statements_count: int,
 *     statements: array<array-key, array<string, mixed>> | list<array{
 *          statement: mixed,
 *          exception: mixed,
 *          alias: string|null
 *      }>,
 * } $data
 */
final class Neo4jDataCollector extends AbstractDataCollector
{
    public function __construct(
        private readonly Neo4jProfileListener $subscriber,
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $t = $this;
        $profiledSummaries = $this->subscriber->getProfiledSummaries();
        $successfulStatements = [];
        foreach ($profiledSummaries as $summary) {
            $statement = ['status' => 'success'];
            foreach ($summary as $key => $value) {
                if (!is_array($value) && !is_object($value)) {
                    $statement[$key] = $value;
                    continue;
                }

                $statement[$key] = $t->recursiveToArray($value);
            }
            $successfulStatements[] = $statement;
        }

        $failedStatements = array_map(
            static fn (array $x) => [
                'status' => 'failure',
                'time' => $x['time'],
                'timestamp' => $x['timestamp'],
                'result' => [
                    'statement' => $x['statement']->toArray(),
                ],
                'exception' => [
                    'code' => $x['exception']->getErrors()[0]->getCode(),
                    'message' => $x['exception']->getErrors()[0]->getMessage(),
                    'classification' => $x['exception']->getErrors()[0]->getClassification(),
                    'category' => $x['exception']->getErrors()[0]->getCategory(),
                    'title' => $x['exception']->getErrors()[0]->getTitle(),
                ],
                'alias' => $x['alias'],
            ],
            $this->subscriber->getProfiledFailures()
        );

        $this->data['successful_statements_count'] = count($successfulStatements);
        $this->data['failed_statements_count'] = count($failedStatements);
        $mergedArray = array_merge($successfulStatements, $failedStatements);
        uasort(
            $mergedArray,
            static fn (array $a, array $b) => $a['start_time'] <=> ($b['timestamp'] ?? $b['start_time'])
        );
        $this->data['statements'] = $mergedArray;
    }

    public function reset(): void
    {
        parent::reset();
        $this->subscriber->reset();
    }

    public function getName(): string
    {
        return 'neo4j';
    }

    /** @api */
    public function getStatements(): array
    {
        return $this->data['statements'];
    }

    public function getSuccessfulStatements(): array
    {
        return array_filter(
            $this->data['statements'],
            static fn (array $x) => 'success' === $x['status']
        );
    }

    public function getFailedStatements(): array
    {
        return array_filter(
            $this->data['statements'],
            static fn (array $x) => 'failure' === $x['status']
        );
    }

    /** @api */
    public function getFailedStatementsCount(): array
    {
        return $this->data['failed_statements_count'];
    }

    /** @api */
    public function getSuccessfulStatementsCount(): array
    {
        return $this->data['successful_statements_count'];
    }

    public function getQueryCount(): int
    {
        return count($this->data['statements']);
    }

    public static function getTemplate(): ?string
    {
        return '@Neo4j/web_profiler.html.twig';
    }

    private function recursiveToArray(mixed $obj): mixed
    {
        if (is_array($obj)) {
            return array_map(
                fn (mixed $x): mixed => $this->recursiveToArray($x),
                $obj
            );
        }

        if (is_object($obj) && method_exists($obj, 'toArray')) {
            return $obj->toArray();
        }

        return $obj;
    }
}
