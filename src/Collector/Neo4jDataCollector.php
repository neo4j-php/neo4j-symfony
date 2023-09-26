<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Collector;

use Laudis\Neo4j\Databags\ResultSummary;
use Neo4j\Neo4jBundle\EventSubscriber\ProfileSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

final class Neo4jDataCollector extends DataCollector
{
    public function __construct(
        private ProfileSubscriber $subscriber
    ) {}

    public function collect(Request $request, Response $response, Throwable $exception = null): void
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
                'alias' => $x['alias']

            ],
            $this->subscriber->getProfiledFailures()
        );
    }

    public function reset(): void
    {
        $this->data = [];
        $this->subscriber->reset();
    }


    public function getName(): string
    {
        return 'neo4j';
    }
}
