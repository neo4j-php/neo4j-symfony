<?php

declare(strict_types=1);

namespace Tests\Neo4j\Neo4jBundle\tests\unit\Decorators;
use Laudis\Neo4j\Contracts\UnmanagedTransactionInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Enum\TransactionState;
use Laudis\Neo4j\Types\CypherList;
use Neo4j\Neo4jBundle\Decorators\SymfonyTransaction;
use Neo4j\Neo4jBundle\EventHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SymfonyTransactionTest extends TestCase
{
    private MockObject&UnmanagedTransactionInterface $mockTransaction;
    private MockObject&EventHandler $mockHandler;
    private SymfonyTransaction $symfonyTransaction;

    protected function setUp(): void
    {
        $this->mockTransaction = $this->createMock(UnmanagedTransactionInterface::class);
        $this->mockHandler = $this->createMock(EventHandler::class);

        $this->symfonyTransaction = new SymfonyTransaction(
            $this->mockTransaction,
            $this->mockHandler,
            'default',
            'bolt',
            'txn-123'
        );
    }


    public function testRun(): void
    {
        $statement = new Statement('MATCH (n) RETURN n', []);
        $mockResult = $this->createMock(SummarizedResult::class);

        $this->mockTransaction
            ->expects($this->once())
            ->method('runStatement')
            ->with($this->equalTo($statement))
            ->willReturn($mockResult);

        $this->mockHandler
            ->expects($this->once())
            ->method('handleQuery')
            ->willReturnCallback(function (callable $callback) use ($statement, $mockResult):mixed {
                return $callback($statement);
            });

        $result = $this->symfonyTransaction->run($statement->getText(), $statement->getParameters());
        $this->assertInstanceOf(SummarizedResult::class, $result);
    }
    public function testCommit(): void
    {
        $this->mockTransaction
            ->expects($this->once())
            ->method('commit')
            ->willReturn(new CypherList([]));
        $this->mockHandler
            ->expects($this->once())
            ->method('handleTransactionAction')
            ->with(
                TransactionState::COMMITTED,
                'txn-123',
                $this->isType('callable'),
                'default',
                'bolt'
            )
            ->willReturnCallback(function (TransactionState $state, string $txnId, callable $callback): mixed {
                return $callback();
            });


        $result = $this->symfonyTransaction->commit();

        $this->assertInstanceOf(CypherList::class, $result);
        $this->assertCount(0, $result);
    }


    public function testRollback(): void
    {
        $this->mockTransaction
            ->expects($this->never())
            ->method('commit');

        $this->mockHandler
            ->expects($this->once())
            ->method('handleTransactionAction')
            ->with(
                TransactionState::ROLLED_BACK,
                'txn-123',
                $this->isType('callable'),
                'default',
                'bolt'
            );

        $this->symfonyTransaction->rollback();
    }

    public function testIsRolledBack(): void
    {
        $this->mockTransaction
            ->expects($this->once())
            ->method('isRolledBack')
            ->willReturn(true);

        $this->assertTrue($this->symfonyTransaction->isRolledBack());
    }

    public function testIsCommitted(): void
    {
        $this->mockTransaction
            ->method('isCommitted')
            ->willReturn(true);

        $this->assertTrue($this->symfonyTransaction->isCommitted());
    }

    public function testIsFinished(): void
    {
        $this->mockTransaction
            ->method('isFinished')
            ->willReturn(true);

        $this->assertTrue($this->symfonyTransaction->isFinished());
    }
}
