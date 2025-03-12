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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventhandlerTest extends TestCase
{
    private MockObject&EventDispatcherInterface $dispatcher;
    private  MockObject&Stopwatch $stopwatch;
    private  MockObject&StopwatchEventNameFactory $nameFactory;
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

        $runHandler = fn(): SummarizedResult => $summary;

        $this->nameFactory->method('createQueryEventName')->willReturn('query_event');

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (object $event, string  $eventId) :object{
                static $callCount = 0;
                $expectedCalls = [
                    [PreRunEvent::class, PreRunEvent::EVENT_ID],
                    [PostRunEvent::class, PostRunEvent::EVENT_ID]
                ];

                [$expectedClass, $expectedId] = $expectedCalls[$callCount++];
                $this->assertInstanceOf($expectedClass, $event);
                $this->assertSame($expectedId, $eventId);

                return $event;
            });

        $this->stopwatch->expects($this->once())->method('start');
        $this->stopwatch->expects($this->once())->method('stop');

        $result = $this->eventHandler->handleQuery($runHandler, $statement, 'alias', 'scheme', 'txId');

        $this->assertSame($summary, $result);
    }


    public function testHandleTransactionAction(): void
    {
        $runHandler = fn(): string => 'result';

        $this->nameFactory->method('createTransactionEventName')->willReturn('tx_event');

        $this->dispatcher->expects($this->exactly(2))->method('dispatch');

        $result = $this->eventHandler->handleTransactionAction(
            TransactionState::COMMITTED,
            'txId',
            $runHandler,
            'alias',
            'scheme'
        );
    }
}
