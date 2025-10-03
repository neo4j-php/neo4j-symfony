<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit;

use Laudis\Neo4j\Basic\Session;
use Laudis\Neo4j\Contracts\ConnectionPoolInterface;
use Laudis\Neo4j\Contracts\CypherSequence;
use Laudis\Neo4j\Databags\Neo4jError;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Enum\AccessMode;
use Laudis\Neo4j\Exception\Neo4jException;
use Neo4j\Neo4jBundle\Decorators\SymfonySession;
use Neo4j\Neo4jBundle\Decorators\SymfonyTransaction;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\Factories\StopwatchEventNameFactory;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for the custom retry logic in SymfonySession writeTransaction method.
 *
 * This test focuses on testing the actual retryTransaction method from SymfonySession
 * using reflection to access the private method. This ensures that changes to the
 * actual implementation are properly tested.
 */
final class SymfonySessionWriteTransactionTest extends TestCase
{
    private SymfonySession $symfonySession;
    private Session $sessionMock;
    private EventHandler $eventHandlerMock;
    private SymfonyDriverFactory $factoryMock;
    private MockObject&ConnectionPoolInterface $poolMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->poolMock = $this->createMock(ConnectionPoolInterface::class);

        $this->sessionMock = new Session(
            session: $this->createMock(\Laudis\Neo4j\Contracts\SessionInterface::class)
        );

        $this->eventHandlerMock = new EventHandler(
            dispatcher: null,
            stopwatch: null,
            nameFactory: new StopwatchEventNameFactory()
        );

        $this->factoryMock = new SymfonyDriverFactory(
            handler: $this->eventHandlerMock,
            uuidFactory: null
        );

        $sessionConfig = new SessionConfiguration();
        $sessionConfig = $sessionConfig->withAccessMode(AccessMode::WRITE());

        $this->symfonySession = new SymfonySession(
            session: $this->sessionMock,
            handler: $this->eventHandlerMock,
            factory: $this->factoryMock,
            alias: 'test-alias',
            schema: 'neo4j',
            config: $sessionConfig,
            pool: $this->poolMock
        );
    }

    /**
     * Call the private retryTransaction method using reflection.
     *
     * @template HandlerResult
     *
     * @param callable(SymfonyTransaction):HandlerResult $tsxHandler
     *
     * @return HandlerResult
     */
    private function callRetryTransaction(callable $tsxHandler, bool $read = false)
    {
        $reflection = new \ReflectionClass($this->symfonySession);
        $method = $reflection->getMethod('retryTransaction');
        $method->setAccessible(true);

        return $method->invoke($this->symfonySession, $tsxHandler, null, $read);
    }

    public function testRetryTransactionSuccess(): void
    {
        $expectedResult = 'transaction-result';

        $tsxHandler = fn (SymfonyTransaction $tx) => $expectedResult;

        $result = $this->callRetryTransaction($tsxHandler);

        $this->assertEquals($expectedResult, $result);
    }

    public function testRetryTransactionWithCypherSequenceResult(): void
    {
        $cypherSequenceMock = $this->createMock(CypherSequence::class);
        $cypherSequenceMock->expects($this->once())
            ->method('preload');

        $tsxHandler = fn (SymfonyTransaction $tx) => $cypherSequenceMock;

        $result = $this->callRetryTransaction($tsxHandler);

        $this->assertSame($cypherSequenceMock, $result);
    }

    public function testRetryTransactionRetryOnTransientError(): void
    {
        $expectedResult = 'transaction-result';
        $transientException = new Neo4jException([new Neo4jError('TransientError', 'Transient error', 'TransientError', 'Transient', 'TransientError')]);

        $callCount = 0;
        $tsxHandler = function (SymfonyTransaction $tx) use (&$callCount, $transientException, $expectedResult) {
            ++$callCount;
            if (1 === $callCount) {
                throw $transientException;
            }

            return $expectedResult;
        };

        $result = $this->callRetryTransaction($tsxHandler);

        $this->assertEquals($expectedResult, $result);
        $this->assertEquals(2, $callCount);
    }

    public function testRetryTransactionMaxRetriesExceeded(): void
    {
        $transientException = new Neo4jException([new Neo4jError('TransientError', 'Transient error', 'TransientError', 'Transient', 'TransientError')]);

        $tsxHandler = fn (SymfonyTransaction $tx) => throw $transientException;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Transient error');

        $this->callRetryTransaction($tsxHandler);
    }

    public function testRetryTransactionNotALeaderErrorClosesPool(): void
    {
        $notALeaderException = new Neo4jException([new Neo4jError('ClientError', 'Not a leader', 'ClientError', 'Client', 'NotALeader')]);

        $this->poolMock->expects($this->atLeastOnce())
            ->method('close');

        $tsxHandler = fn (SymfonyTransaction $tx) => throw $notALeaderException;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Not a leader');

        $this->callRetryTransaction($tsxHandler);
    }

    public function testRetryTransactionNonTransientErrorThrownImmediately(): void
    {
        $clientError = new Neo4jException([new Neo4jError('ClientError', 'Client error', 'ClientError', 'Client', 'ClientError')]);

        $tsxHandler = fn (SymfonyTransaction $tx) => throw $clientError;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Client error');

        $this->callRetryTransaction($tsxHandler);
    }

    public function testRetryTransactionDatabaseErrorThrownImmediately(): void
    {
        $databaseError = new Neo4jException([new Neo4jError('DatabaseError', 'Database error', 'DatabaseError', 'Database', 'DatabaseError')]);

        $tsxHandler = fn (SymfonyTransaction $tx) => throw $databaseError;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Database error');

        $this->callRetryTransaction($tsxHandler);
    }

    public function testRetryTransactionNoRollbackForNonRollbackClassifications(): void
    {
        $nonRollbackException = new Neo4jException([new Neo4jError('UnknownError', 'Non-rollback error', 'UnknownError', 'Unknown', 'UnknownError')]);

        $tsxHandler = fn (SymfonyTransaction $tx) => throw $nonRollbackException;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Non-rollback error');

        $this->callRetryTransaction($tsxHandler);
    }

    public function testRetryTransactionMultipleTransientErrors(): void
    {
        $expectedResult = 'transaction-result';
        $transientException = new Neo4jException([new Neo4jError('TransientError', 'Transient error', 'TransientError', 'Transient', 'TransientError')]);

        $callCount = 0;
        $tsxHandler = function (SymfonyTransaction $tx) use (&$callCount, $transientException, $expectedResult) {
            ++$callCount;
            if ($callCount <= 2) {
                throw $transientException;
            }

            return $expectedResult;
        };

        $result = $this->callRetryTransaction($tsxHandler);

        $this->assertEquals($expectedResult, $result);
        $this->assertEquals(3, $callCount);
    }

    public function testRetryTransactionRetryDelay(): void
    {
        $transientException = new Neo4jException([new Neo4jError('TransientError', 'Transient error', 'TransientError', 'Transient', 'TransientError')]);

        $callCount = 0;
        $tsxHandler = function (SymfonyTransaction $tx) use (&$callCount, $transientException) {
            ++$callCount;
            if (1 === $callCount) {
                throw $transientException;
            }

            return 'success';
        };

        $startTime = microtime(true);

        $result = $this->callRetryTransaction($tsxHandler);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertEquals('success', $result);
        $this->assertGreaterThan(0.1, $duration);
    }

    public function testRetryTransactionWithReadTransaction(): void
    {
        $expectedResult = 'read-transaction-result';

        $tsxHandler = fn (SymfonyTransaction $tx) => $expectedResult;

        $result = $this->callRetryTransaction($tsxHandler, read: true);

        $this->assertEquals($expectedResult, $result);
    }

    public function testRetryTransactionConstants(): void
    {
        $reflection = new \ReflectionClass($this->symfonySession);

        $maxRetries = $reflection->getConstant('MAX_RETRIES');
        $rollbackClassifications = $reflection->getConstant('ROLLBACK_CLASSIFICATIONS');

        $this->assertEquals(3, $maxRetries);
        $this->assertEquals(['ClientError', 'TransientError', 'DatabaseError'], $rollbackClassifications);
    }
}
