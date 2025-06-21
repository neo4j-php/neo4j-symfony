<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit\Event;

use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Exception\Neo4jException;
use Neo4j\Neo4jBundle\Event\FailureEvent;
use PHPUnit\Framework\TestCase;

class FailureEventTest extends TestCase
{
    public function testEventProperties(): void
    {
        $alias = 'test_alias';
        $statement = $this->createMock(Statement::class);
        $exception = $this->createMock(Neo4jException::class);
        $time = new \DateTimeImmutable();
        $scheme = 'bolt';
        $transactionId = '1234';

        $event = new FailureEvent(
            alias: $alias,
            statement: $statement,
            exception: $exception,
            time: $time,
            scheme: $scheme,
            transactionId: $transactionId
        );

        $this->assertSame($alias, $event->alias);
        $this->assertSame($statement, $event->statement);
        $this->assertSame($exception, $event->exception);
        $this->assertSame($time, $event->time);
        $this->assertSame($scheme, $event->scheme);
        $this->assertSame($transactionId, $event->transactionId);
    }

    public function testDisableException(): void
    {
        $event = new FailureEvent(
            alias: 'test_alias',
            statement: null,
            exception: $this->createMock(Neo4jException::class),
            time: new \DateTimeImmutable(),
            scheme: 'bolt',
            transactionId: '1234'
        );

        $this->assertTrue($this->getShouldThrowException($event));

        $event->disableException();

        $this->assertFalse($this->getShouldThrowException($event));
    }

    private function getShouldThrowException(FailureEvent $event): bool
    {
        $reflection = new \ReflectionClass(FailureEvent::class);
        $property = $reflection->getProperty('shouldThrowException');

        return $property->getValue($event);
    }
}
