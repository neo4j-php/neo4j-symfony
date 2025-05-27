<?php

namespace Neo4j\Neo4jBundle\Tests\Unit\Decorators;

use Laudis\Neo4j\Basic\Session;
use Laudis\Neo4j\Databags\Bookmark;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Neo4j\Neo4jBundle\Decorators\SymfonySession;
use Neo4j\Neo4jBundle\Decorators\SymfonyTransaction;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SymfonySessionTest extends TestCase
{
    private MockObject&Session $sessionMock;
    private MockObject&EventHandler $handlerMock;
    private MockObject&SymfonyDriverFactory $factoryMock;
    private SymfonySession $symfonySession;
    private string $alias = 'default';
    private string $schema = 'neo4j';

    protected function setUp(): void
    {

        $this->sessionMock = $this->createMock(Session::class);
        $this->handlerMock = $this->createMock(EventHandler::class);
        $this->factoryMock = $this->createMock(SymfonyDriverFactory::class);

        $this->symfonySession = new SymfonySession(
            $this->sessionMock,
            $this->createMock(EventHandler::class),
            $this->createMock(SymfonyDriverFactory::class),
            'default',
            'bolt'
        );

        $this->symfonySession = new SymfonySession(
            $this->sessionMock,
            $this->handlerMock,
            $this->factoryMock,
            $this->alias,
            $this->schema
        );
    }

    public function testRunStatement():void
    {
        $statementMock = $this->createMock(Statement::class);
        $resultMock = $this->createMock(SummarizedResult::class);


        $this->handlerMock
            ->expects($this->once())
            ->method('handleQuery')
            ->with(
                $this->callback(fn($callback) => is_callable($callback)),
                $statementMock,
                $this->alias,
                $this->schema,
                null
            )
            ->willReturn($resultMock);

        $result = $this->symfonySession->runStatement($statementMock);
        $this->assertInstanceOf(SummarizedResult::class, $result);
    }

    public function testRunStatements():void
    {
        $statementMock1 = $this->createMock(Statement::class);
        $statementMock2 = $this->createMock(Statement::class);
        $resultMock1 = $this->createMock(SummarizedResult::class);
        $resultMock2 = $this->createMock(SummarizedResult::class);


        $this->handlerMock
            ->method('handleQuery')
            ->willReturnOnConsecutiveCalls($resultMock1, $resultMock2);

        $result = $this->symfonySession->runStatements([$statementMock1, $statementMock2]);
        $this->assertInstanceOf(CypherList::class, $result);
        $this->assertCount(2, $result);
    }


    public function testBeginTransaction():void
    {
        $transactionMock = $this->createMock(SymfonyTransaction::class);


        $this->factoryMock
            ->expects($this->once())
            ->method('createTransaction')
            ->with(
                $this->sessionMock,
                $this->anything(),
                $this->alias,
                $this->schema
            )
            ->willReturn($transactionMock);

        $transaction = $this->symfonySession->beginTransaction();

        $this->assertInstanceOf(SymfonyTransaction::class, $transaction);
    }

    public function testWriteTransaction():void
    {
        $transactionMock = $this->createMock(SymfonyTransaction::class);

        $this->factoryMock
            ->expects($this->once())
            ->method('createTransaction')
            ->willReturn($transactionMock);

        $handler = function ( SymfonyTransaction $tsx) :string {
            return 'transaction success';
        };

        $result = $this->symfonySession->writeTransaction($handler);

        $this->assertEquals('transaction success', $result);
    }

    public function testGetLastBookmark():void
    {
        $bookmarkMock = $this->createMock(Bookmark::class);

        $this->sessionMock
            ->expects($this->once())
            ->method('getLastBookmark')
            ->willReturn($bookmarkMock);

        $bookmark = $this->symfonySession->getLastBookmark();
        $this->assertInstanceOf(Bookmark::class, $bookmark);
    }

}
