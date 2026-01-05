<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit;

use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Enum\TransactionState;
use Laudis\Neo4j\Exception\Neo4jException;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\Event\FailureEvent;
use Neo4j\Neo4jBundle\Event\PostRunEvent;
use Neo4j\Neo4jBundle\Event\PreRunEvent;
use Neo4j\Neo4jBundle\Factories\StopwatchEventNameFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventHandlerTest extends TestCase
{
    private EventDispatcherInterface $dispatcher;
    private Stopwatch $stopwatch;
    private StopwatchEventNameFactory $nameFactory;
    private EventHandler $eventHandler;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->nameFactory = $this->createMock(StopwatchEventNameFactory::class);

        $this->eventHandler = new EventHandler(
            dispatcher: $this->dispatcher,
            stopwatch: $this->stopwatch,
            nameFactory: $this->nameFactory
        );
    }

    public function testHandleQuery(): void
    {
        $statement = $this->createMock(Statement::class);
        $summary = $this->createMock(SummarizedResult::class);
        $resultSummary = $this->createMock(\Laudis\Neo4j\Databags\ResultSummary::class);

        $summary->method('getSummary')->willReturn($resultSummary);

        $runHandler = fn() => $summary;

        $this->nameFactory->method('createQueryEventName')->willReturn('query_event');

        $this->dispatcher->expects($this->exactly(2))->method('dispatch')->withConsecutive(
            [$this->isInstanceOf(PreRunEvent::class), PreRunEvent::EVENT_ID],
            [$this->isInstanceOf(PostRunEvent::class), PostRunEvent::EVENT_ID]
        );

        $this->stopwatch->expects($this->once())->method('start');
        $this->stopwatch->expects($this->once())->method('stop');

        $result = $this->eventHandler->handleQuery($runHandler, $statement, 'alias', 'scheme', 'txId');

        $this->assertSame($summary, $result);
    }


    public function testHandleTransactionAction(): void
    {
        $runHandler = fn() => 'result';

        $this->nameFactory->method('createTransactionEventName')->willReturn('tx_event');

        $this->dispatcher->expects($this->exactly(2))->method('dispatch');

        $result = $this->eventHandler->handleTransactionAction(TransactionState::COMMITTED, 'txId', $runHandler, 'alias', 'scheme');

        $this->assertSame('result', $result);
    }
}
