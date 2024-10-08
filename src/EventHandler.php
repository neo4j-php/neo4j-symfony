<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle;

use Laudis\Neo4j\Common\Uri;
use Laudis\Neo4j\Databags\DatabaseInfo;
use Laudis\Neo4j\Databags\ResultSummary;
use Laudis\Neo4j\Databags\ServerInfo;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Databags\SummaryCounters;
use Laudis\Neo4j\Enum\ConnectionProtocol;
use Laudis\Neo4j\Enum\QueryTypeEnum;
use Laudis\Neo4j\Exception\Neo4jException;
use Laudis\Neo4j\Types\CypherList;
use Neo4j\Neo4jBundle\Event\FailureEvent;
use Neo4j\Neo4jBundle\Event\PostRunEvent;
use Neo4j\Neo4jBundle\Event\PreRunEvent;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventHandler
{
    private ?EventDispatcherInterface $dispatcher;

    public function __construct(
        ?EventDispatcherInterface $dispatcher,
        private readonly string $alias,
        private readonly ?Stopwatch $stopwatch,
    ) {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @template T
     *
     * @param callable(Statement):SummarizedResult<T> $runHandler
     *
     * @return SummarizedResult<T>
     */
    public function handle(callable $runHandler, Statement $statement, ?string $alias, ?string $scheme): SummarizedResult
    {
        $stopWatchName = sprintf('neo4j.%s.query', $alias ?? $this->alias);
        if (null === $this->dispatcher) {
            $this->stopwatch?->start($stopWatchName);
            $result = $runHandler($statement);
            $this->stopwatch?->stop($stopWatchName);

            return $result;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $time = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
        $this->dispatcher->dispatch(new PreRunEvent($alias, $statement, $time, $scheme), PreRunEvent::EVENT_ID);

        try {
            $this->stopwatch?->start($stopWatchName);
            $tbr = $runHandler($statement);
            $this->stopwatch?->stop($stopWatchName);
            $this->dispatcher->dispatch(
                new PostRunEvent($alias ?? $this->alias, $tbr->getSummary(), $time, $scheme),
                PostRunEvent::EVENT_ID
            );
        } catch (Neo4jException $e) {
            $this->stopwatch?->stop($stopWatchName);
            /** @noinspection PhpUnhandledExceptionInspection */
            $time = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
            $event = new FailureEvent($alias ?? $this->alias, $statement, $e, $time, $scheme);
            $event = $this->dispatcher->dispatch($event, FailureEvent::EVENT_ID);

            if ($event->shouldThrowException()) {
                throw $e;
            }

            $summary = new ResultSummary(
                new SummaryCounters(),
                new DatabaseInfo('n/a'),
                new CypherList([]),
                null,
                null,
                $statement,
                QueryTypeEnum::READ_ONLY(),
                0,
                0,
                new ServerInfo(Uri::create(''), ConnectionProtocol::BOLT_V5(), 'n/a'),
            );

            $tbr = new SummarizedResult($summary);
        }

        return $tbr;
    }
}
