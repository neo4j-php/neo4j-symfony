<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle;

use DateTimeImmutable;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Enum\TransactionState;
use Laudis\Neo4j\Exception\Neo4jException;
use Neo4j\Neo4jBundle\Event\FailureEvent;
use Neo4j\Neo4jBundle\Event\PostRunEvent;
use Neo4j\Neo4jBundle\Event\PreRunEvent;
use Neo4j\Neo4jBundle\Event\Transaction\PostTransactionBeginEvent;
use Neo4j\Neo4jBundle\Event\Transaction\PostTransactionCommitEvent;
use Neo4j\Neo4jBundle\Event\Transaction\PostTransactionRollbackEvent;
use Neo4j\Neo4jBundle\Event\Transaction\PreTransactionBeginEvent;
use Neo4j\Neo4jBundle\Event\Transaction\PreTransactionCommitEvent;
use Neo4j\Neo4jBundle\Event\Transaction\PreTransactionRollbackEvent;
use Neo4j\Neo4jBundle\Factories\StopwatchEventNameFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use UnexpectedValueException;

final class EventHandler
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

        $time = new DateTimeImmutable();
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
            runHandler: $runHandler,
            alias: $alias,
            scheme: $scheme,
            stopwatchName: $stopwatchName,
            transactionId: $transactionId,
            statement: $statement
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

        [
            'preEvent' => $preEvent,
            'preEventId' => $preEventId,
            'postEvent' => $postEvent,
            'postEventId' => $postEventId,
        ] = $this->createPreAndPostEventsAndIds(
            nextTransactionState: $nextTransactionState,
            alias: $alias,
            scheme: $scheme,
            transactionId: $transactionId
        );

        $this->dispatcher?->dispatch($preEvent, $preEventId);
        $result = $this->handleAction(runHandler: $runHandler, alias: $alias, scheme: $scheme, stopwatchName: $stopWatchName, transactionId: $transactionId, statement: null);
        $this->dispatcher?->dispatch($postEvent, $postEventId);

        return $result;
    }

    /**
     * @template T
     *
     * @param callable():T $runHandler
     *
     * @return T
     */
    private function handleAction(callable $runHandler, string $alias, string $scheme, string $stopwatchName, ?string $transactionId, ?Statement $statement): mixed
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
                statement: $statement,
                exception: $e,
                time: new DateTimeImmutable('now'),
                scheme: $scheme,
                transactionId: $transactionId
            );

            $this->dispatcher?->dispatch($event, FailureEvent::EVENT_ID);

            throw $e;
        }
    }

    /**
     * @return array{'preEvent': object, 'preEventId': string, 'postEvent': object, 'postEventId': string}
     */
    private function createPreAndPostEventsAndIds(
        TransactionState $nextTransactionState,
        string $alias,
        string $scheme,
        string $transactionId,
    ): array {
        [$preEvent, $preEventId] = match ($nextTransactionState) {
            TransactionState::ACTIVE => [
                new PreTransactionBeginEvent(
                    alias: $alias,
                    time: new DateTimeImmutable(),
                    scheme: $scheme,
                    transactionId: $transactionId,
                ),
                PreTransactionBeginEvent::EVENT_ID,
            ],
            TransactionState::ROLLED_BACK => [
                new PreTransactionRollbackEvent(
                    alias: $alias,
                    time: new DateTimeImmutable(),
                    scheme: $scheme,
                    transactionId: $transactionId,
                ),
                PreTransactionRollbackEvent::EVENT_ID,
            ],
            TransactionState::COMMITTED => [
                new PreTransactionCommitEvent(
                    alias: $alias,
                    time: new DateTimeImmutable(),
                    scheme: $scheme,
                    transactionId: $transactionId,
                ),
                PreTransactionCommitEvent::EVENT_ID,
            ],
            TransactionState::TERMINATED => throw new UnexpectedValueException('TERMINATED is not a valid transaction state at this point'),
        };
        [$postEvent, $postEventId] = match ($nextTransactionState) {
            TransactionState::ACTIVE => [
                new PostTransactionBeginEvent(
                    alias: $alias,
                    time: new DateTimeImmutable(),
                    scheme: $scheme,
                    transactionId: $transactionId,
                ),
                PostTransactionBeginEvent::EVENT_ID,
            ],
            TransactionState::ROLLED_BACK => [
                new PostTransactionRollbackEvent(
                    alias: $alias,
                    time: new DateTimeImmutable(),
                    scheme: $scheme,
                    transactionId: $transactionId,
                ),
                PostTransactionRollbackEvent::EVENT_ID,
            ],
            TransactionState::COMMITTED => [
                new PostTransactionCommitEvent(
                    alias: $alias,
                    time: new DateTimeImmutable(),
                    scheme: $scheme,
                    transactionId: $transactionId,
                ),
                PostTransactionCommitEvent::EVENT_ID,
            ],
            TransactionState::TERMINATED => throw new UnexpectedValueException('TERMINATED is not a valid transaction state at this point'),
        };

        return [
            'preEvent' => $preEvent,
            'preEventId' => $preEventId,
            'postEvent' => $postEvent,
            'postEventId' => $postEventId,
        ];
    }
}
