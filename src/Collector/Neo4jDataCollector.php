<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Collector;

use Laudis\Neo4j\Databags\ResultSummary;
use Neo4j\Neo4jBundle\EventListener\Neo4jProfileListener;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @var array{
 *     successful_statements: array<array-key, array<string, mixed>>,
 *     failed_statements: list<array{
 *         statement: mixed,
 *         exception: mixed,
 *         alias: string|null
 *     }>
 * } $data
 */
final class Neo4jDataCollector extends AbstractDataCollector
{
    public function __construct(
        private Neo4jProfileListener $subscriber
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data['successful_statements'] = array_map(
            static fn (ResultSummary $summary) => $summary->toArray(),
            $this->subscriber->getProfiledSummaries()
        );

        $this->data['failed_statements'] = array_map(
            static fn (array $x) => [
                'statement' => $x['statement']->toArray(),
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

    public function getFailedStatements(): array
    {
        return $this->data['failed_statements'];
    }

    public function getSuccessfulStatements(): array
    {
        return $this->data['successful_statements'];
    }

    public function getQueryCount(): int
    {
        return count($this->data['successful_statements']) + count($this->data['failed_statements']);
    }

    public static function getTemplate(): ?string
    {
        return 'data_collector/web_profiler.html.twig';
    }
}
