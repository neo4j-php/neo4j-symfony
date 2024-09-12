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
        private readonly Neo4jProfileListener $subscriber
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $profiledSummaries = $this->subscriber->getProfiledSummaries();
        $successfulStatements = array_map(
            static function (string $key, mixed $value) {
                if ('result' !== $key && /* Is always array */ is_array($value)) {
                    return [
                        ...$value,
                        'status' => 'success',
                    ];
                }

                return array_map(
                    static function (mixed $obj) {
                        if (is_object($obj) && method_exists($obj, 'toArray')) {
                            return $obj->toArray();
                        }

                        return $obj;
                    },
                    $value['result']->toArray()
                );
            },
            array_keys($profiledSummaries),
            array_values($profiledSummaries)
        );

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
            static fn (array $a, array $b) => $a['start_time'] <=> $b['timestamp']
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

    public function getFailedStatementsCount(): array
    {
        return $this->data['failed_statements_count'];
    }

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
}
