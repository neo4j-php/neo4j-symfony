<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle;

use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Enum\TransactionState;
use Laudis\Neo4j\Exception\Neo4jException;
use Neo4j\Neo4jBundle\Event\FailureEvent;
use Neo4j\Neo4jBundle\Event\PostRunEvent;
use Neo4j\Neo4jBundle\Event\PreRunEvent;
use Neo4j\Neo4jBundle\Event\TransactionEvent;
use Neo4j\Neo4jBundle\Factories\StopwatchEventNameFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventHandler
{
    private ?EventDispatcherInterface $dispatcher;

    public function __construct(
        ?EventDispatcherInterface $dispatcher,
        private readonly ?Stopwatch $stopwatch,
        private readonly StopwatchEventNameFactory $nameFactory,
    ) {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @template T
     *
     * @param callable(Statement):T $runHandler
     *
     * @return T
     */
    public function handleQuery(callable $runHandler, Statement $statement, string $alias, string $scheme, ?string $transactionId): SummarizedResult
    {
        $stopwatchName = $this->nameFactory->createQueryEventName($alias, $transactionId);

        $time = new \DateTimeImmutable();
        $event = new PreRunEvent(
            alias: $alias,
            statement: $statement,
            time: $time,
            scheme: $scheme,
            transactionId: $transactionId
        );

        $this->dispatcher?->dispatch($event, PreRunEvent::EVENT_ID);

        $runHandler = static fn (): mixed => $runHandler($statement);
        $result = $this->handleAction(
            $runHandler,
            $alias,
            $scheme,
            $stopwatchName,
            $transactionId
        );

        $event = new PostRunEvent(
            alias: $alias,
            result: $result->getSummary(),
            time: $time,
            scheme: $scheme,
            transactionId: $transactionId
        );

        $this->dispatcher?->dispatch(
            $event,
            PostRunEvent::EVENT_ID
        );

        return $result;
    }

    /**
     * @template T
     *
     * @param callable():T $runHandler
     *
     * @return T
     */
    public function handleTransactionAction(
        TransactionState $nextTransactionState,
        string $transactionId,
        callable $runHandler,
        string $alias,
        string $scheme,
    ): mixed {
        $stopWatchName = $this->nameFactory->createTransactionEventName($alias, $transactionId, $nextTransactionState);

        if (TransactionState::ACTIVE === $nextTransactionState) {
            $this->dispatchTransactionEvent($alias, $scheme, $transactionId);
        }

        $result = $this->handleAction($runHandler, $alias, $scheme, $stopWatchName, $transactionId);

        if (TransactionState::COMMITTED === $nextTransactionState
            || TransactionState::ROLLED_BACK === $nextTransactionState) {
            $this->dispatchTransactionEvent($alias, $scheme, $transactionId);
        }

        return $result;
    }

    /**
     * @template T
     *
     * @param callable():T $runHandler
     *
     * @return T
     */
    private function handleAction(callable $runHandler, string $alias, string $scheme, string $stopwatchName, ?string $transactionId): mixed
    {
        try {
            $this->stopwatch?->start($stopwatchName, 'database');
            $result = $runHandler();
            $this->stopwatch?->stop($stopwatchName);

            return $result;
        } catch (Neo4jException $e) {
            $this->stopwatch?->stop($stopwatchName);
            $event = new FailureEvent(
                alias: $alias,
                exception: $e,
                time: new \DateTimeImmutable('now'),
                scheme: $scheme,
                transactionId: $transactionId
            );

            $this->dispatcher?->dispatch($event, FailureEvent::EVENT_ID);

            throw $e;
        }
    }

    private function dispatchTransactionEvent(?string $alias, string $scheme, string $transactionId): void
    {
        $event = new TransactionEvent(
            alias: $alias,
            time: new \DateTimeImmutable(),
            scheme: $scheme,
            transactionId: $transactionId,
        );

        $this->dispatcher?->dispatch($event, TransactionEvent::EVENT_ID);
    }
}
