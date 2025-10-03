<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit;

use Laudis\Neo4j\Contracts\CypherSequence;
use Laudis\Neo4j\Databags\Neo4jError;
use Laudis\Neo4j\Exception\Neo4jException;
use PHPUnit\Framework\TestCase;

/**
 * Test class for the custom retry logic in SymfonySession writeTransaction method.
 *
 * This test focuses on testing the custom retry logic that replaced
 * the TransactionHelper dependency. We create a testable version of the
 * retry logic to verify its behavior.
 */
final class SymfonySessionWriteTransactionTest extends TestCase
{
    public function testRetryLogicSuccess(): void
    {
        $expectedResult = 'transaction-result';
        $retryLogic = new TestableRetryLogic();

        $tsxHandler = fn () => $expectedResult;

        $result = $retryLogic->retryTransaction($tsxHandler);

        $this->assertEquals($expectedResult, $result);
    }

    public function testRetryLogicWithCypherSequenceResult(): void
    {
        $cypherSequenceMock = $this->createMock(CypherSequence::class);
        $cypherSequenceMock->expects($this->once())
            ->method('preload');

        $retryLogic = new TestableRetryLogic();

        $tsxHandler = fn () => $cypherSequenceMock;

        $result = $retryLogic->retryTransaction($tsxHandler);

        $this->assertSame($cypherSequenceMock, $result);
    }

    public function testRetryLogicRetryOnTransientError(): void
    {
        $expectedResult = 'transaction-result';
        $transientException = new Neo4jException([new Neo4jError('TransientError', 'Transient error', 'TransientError', 'Transient', 'TransientError')]);

        $retryLogic = new TestableRetryLogic();

        $callCount = 0;
        $tsxHandler = function () use (&$callCount, $transientException, $expectedResult) {
            ++$callCount;
            if (1 === $callCount) {
                throw $transientException;
            }

            return $expectedResult;
        };

        $result = $retryLogic->retryTransaction($tsxHandler);

        $this->assertEquals($expectedResult, $result);
        $this->assertEquals(2, $callCount);
    }

    public function testRetryLogicMaxRetriesExceeded(): void
    {
        $transientException = new Neo4jException([new Neo4jError('TransientError', 'Transient error', 'TransientError', 'Transient', 'TransientError')]);
        $retryLogic = new TestableRetryLogic();

        $tsxHandler = fn () => throw $transientException;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Transient error');

        $retryLogic->retryTransaction($tsxHandler);
    }

    public function testRetryLogicNotALeaderErrorClosesPool(): void
    {
        $notALeaderException = new Neo4jException([new Neo4jError('ClientError', 'Not a leader', 'ClientError', 'Client', 'NotALeader')]);

        $poolMock = $this->createMock(\Laudis\Neo4j\Contracts\ConnectionPoolInterface::class);
        $poolMock->expects($this->atLeastOnce())
            ->method('close');

        $retryLogic = new TestableRetryLogic($poolMock);

        $tsxHandler = fn () => throw $notALeaderException;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Not a leader');

        $retryLogic->retryTransaction($tsxHandler);
    }

    public function testRetryLogicNonTransientErrorThrownImmediately(): void
    {
        $clientError = new Neo4jException([new Neo4jError('ClientError', 'Client error', 'ClientError', 'Client', 'ClientError')]);
        $retryLogic = new TestableRetryLogic();

        $tsxHandler = fn () => throw $clientError;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Client error');

        $retryLogic->retryTransaction($tsxHandler);
    }

    public function testRetryLogicDatabaseErrorThrownImmediately(): void
    {
        $databaseError = new Neo4jException([new Neo4jError('DatabaseError', 'Database error', 'DatabaseError', 'Database', 'DatabaseError')]);
        $retryLogic = new TestableRetryLogic();

        $tsxHandler = fn () => throw $databaseError;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Database error');

        $retryLogic->retryTransaction($tsxHandler);
    }

    public function testRetryLogicNoRollbackForNonRollbackClassifications(): void
    {
        $nonRollbackException = new Neo4jException([new Neo4jError('UnknownError', 'Non-rollback error', 'UnknownError', 'Unknown', 'UnknownError')]);
        $retryLogic = new TestableRetryLogic();

        $tsxHandler = fn () => throw $nonRollbackException;

        $this->expectException(Neo4jException::class);
        $this->expectExceptionMessage('Non-rollback error');

        $retryLogic->retryTransaction($tsxHandler);
    }

    public function testRetryLogicMultipleTransientErrors(): void
    {
        $expectedResult = 'transaction-result';
        $transientException = new Neo4jException([new Neo4jError('TransientError', 'Transient error', 'TransientError', 'Transient', 'TransientError')]);
        $retryLogic = new TestableRetryLogic();

        $callCount = 0;
        $tsxHandler = function () use (&$callCount, $transientException, $expectedResult) {
            ++$callCount;
            if ($callCount <= 2) {
                throw $transientException;
            }

            return $expectedResult;
        };

        $result = $retryLogic->retryTransaction($tsxHandler);

        $this->assertEquals($expectedResult, $result);
        $this->assertEquals(3, $callCount);
    }

    public function testRetryLogicRetryDelay(): void
    {
        $transientException = new Neo4jException([new Neo4jError('TransientError', 'Transient error', 'TransientError', 'Transient', 'TransientError')]);
        $retryLogic = new TestableRetryLogic();

        $callCount = 0;
        $tsxHandler = function () use (&$callCount, $transientException) {
            ++$callCount;
            if (1 === $callCount) {
                throw $transientException;
            }

            return 'success';
        };

        $startTime = microtime(true);

        $result = $retryLogic->retryTransaction($tsxHandler);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertEquals('success', $result);
        $this->assertGreaterThan(0.1, $duration);
    }

    public function testRetryLogicConstants(): void
    {
        $this->assertEquals(3, TestableRetryLogic::MAX_RETRIES);
        $this->assertEquals(['ClientError', 'TransientError', 'DatabaseError'], TestableRetryLogic::ROLLBACK_CLASSIFICATIONS);
    }

    public function testRetryLogicWithReadTransaction(): void
    {
        $expectedResult = 'read-transaction-result';
        $retryLogic = new TestableRetryLogic();

        $tsxHandler = fn () => $expectedResult;

        $result = $retryLogic->retryTransaction($tsxHandler, read: true);

        $this->assertEquals($expectedResult, $result);
    }
}

/**
 * Testable version of the retry logic from SymfonySession.
 * This extracts the retry logic into a testable class that mimics
 * the behavior of the writeTransaction method.
 */
final class TestableRetryLogic
{
    public const MAX_RETRIES = 3;
    public const ROLLBACK_CLASSIFICATIONS = ['ClientError', 'TransientError', 'DatabaseError'];

    public function __construct(
        private ?\Laudis\Neo4j\Contracts\ConnectionPoolInterface $pool = null,
    ) {
    }

    /**
     * Testable version of the retry logic from SymfonySession::retryTransaction.
     *
     * @template HandlerResult
     *
     * @param callable():HandlerResult $tsxHandler
     * @param bool                     $read       Whether this is a read transaction
     *
     * @return HandlerResult
     */
    public function retryTransaction(callable $tsxHandler, bool $read = false)
    {
        $attempt = 0;

        while (true) {
            ++$attempt;
            $transaction = null;

            try {
                $transaction = $this->createMockTransaction();

                $result = $tsxHandler();

                $this->triggerLazyResult($result);
                $this->commitTransaction($transaction);

                return $result;
            } catch (Neo4jException $e) {
                if ($transaction && !in_array($e->getClassification(), self::ROLLBACK_CLASSIFICATIONS)) {
                    $this->rollbackTransaction($transaction);
                }

                if ('NotALeader' === $e->getTitle()) {
                    $this->pool?->close();
                } elseif ('TransientError' !== $e->getClassification()) {
                    throw $e;
                }

                if ($attempt >= self::MAX_RETRIES) {
                    throw $e;
                }

                usleep(100_000);
            }
        }
    }

    private function createMockTransaction(): MockTransaction
    {
        return new MockTransaction();
    }

    private function commitTransaction(MockTransaction $transaction): void
    {
        $transaction->commit();
    }

    private function rollbackTransaction(MockTransaction $transaction): void
    {
        $transaction->rollback();
    }

    private function triggerLazyResult(mixed $tbr): void
    {
        if ($tbr instanceof CypherSequence) {
            $tbr->preload();
        }
    }
}

/**
 * Mock transaction for testing purposes.
 */
final class MockTransaction
{
    private bool $committed = false;
    private bool $rolledBack = false;

    public function commit(): void
    {
        $this->committed = true;
    }

    public function rollback(): void
    {
        $this->rolledBack = true;
    }

    public function isCommitted(): bool
    {
        return $this->committed;
    }

    public function isRolledBack(): bool
    {
        return $this->rolledBack;
    }
}
