<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit\EventListener;

use Laudis\Neo4j\Databags\ResultSummary;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Exception\Neo4jException;
use Neo4j\Neo4jBundle\Event\FailureEvent;
use Neo4j\Neo4jBundle\Event\PostRunEvent;
use Neo4j\Neo4jBundle\EventListener\Neo4jProfileListener;
use PHPUnit\Framework\TestCase;

class Neo4jProfileListenerTest extends TestCase
{
    public function testOnPostRun(): void
    {
        $enabledProfiles = ['default'];
        $listener = new Neo4jProfileListener($enabledProfiles);

        $alias = 'default';
        $resultMock = $this->createMock(ResultSummary::class);
        $resultMock->method('getResultAvailableAfter')->willReturn(10.0);
        $resultMock->method('getResultConsumedAfter')->willReturn(5.0);

        $time = new \DateTimeImmutable();
        $scheme = 'bolt';
        $transactionId = 'tx123';

        $event = new PostRunEvent($alias, $resultMock, $time, $scheme, $transactionId);

        $listener->onPostRun($event);

        $profiledSummaries = $listener->getProfiledSummaries();

        $this->assertCount(1, $profiledSummaries);
        $this->assertSame($resultMock, $profiledSummaries[0]['result']);
        $this->assertSame($alias, $profiledSummaries[0]['alias']);
        $this->assertSame($scheme, $profiledSummaries[0]['scheme']);
        $this->assertSame($transactionId, $profiledSummaries[0]['transaction_id']);
        $this->assertSame($time->format('Y-m-d H:i:s'), $profiledSummaries[0]['time']);
    }


    public function testOnFailure(): void
    {
        $enabledProfiles = ['default'];
        $listener = new Neo4jProfileListener($enabledProfiles);

        $alias = 'default';
        $statementMock = $this->createMock(Statement::class);
        $exceptionMock = $this->createMock(Neo4jException::class);
        $time = new \DateTimeImmutable();

        $event = new FailureEvent($alias, $statementMock, $exceptionMock, $time, 'bolt', 'tx123');

        $listener->onFailure($event);

        $profiledFailures = $listener->getProfiledFailures();

        $this->assertCount(1, $profiledFailures);
        $this->assertSame($exceptionMock, $profiledFailures[0]['exception']);
        $this->assertSame($statementMock, $profiledFailures[0]['statement']);
        $this->assertSame($alias, $profiledFailures[0]['alias']);
        $this->assertSame($time->format('Y-m-d H:i:s'), $profiledFailures[0]['time']);
    }

    public function testReset(): void
    {
        $enabledProfiles = ['default'];
        $listener = new Neo4jProfileListener($enabledProfiles);

        $resultMock = $this->createMock(ResultSummary::class);
        $exceptionMock = $this->createMock(Neo4jException::class);
        $statementMock = $this->createMock(Statement::class);
        $time = new \DateTimeImmutable();

        $listener->onPostRun(new PostRunEvent('default', $resultMock, $time, 'bolt', 'tx123'));
        $listener->onFailure(new FailureEvent('default', $statementMock, $exceptionMock, $time, 'bolt', 'tx123'));

        $this->assertNotEmpty($listener->getProfiledSummaries());
        $this->assertNotEmpty($listener->getProfiledFailures());

        $listener->reset();

        $this->assertEmpty($listener->getProfiledSummaries());
        $this->assertEmpty($listener->getProfiledFailures());
    }
}
