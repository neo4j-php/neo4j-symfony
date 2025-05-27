<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Decorators;

use Laudis\Neo4j\Common\DriverSetupManager;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Neo4j\Neo4jBundle\Decorators\SymfonyClient;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;
use Neo4j\Neo4jBundle\Decorators\SymfonySession;
use Neo4j\Neo4jBundle\Decorators\SymfonyTransaction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SymfonyClientTest extends TestCase
{
    private SymfonyClient $client;
    private DriverSetupManager $driverSetupManagerMock;
    private SessionConfiguration $sessionConfig;
    private TransactionConfiguration $transactionConfig;
    private SymfonyDriverFactory $driverFactoryMock;
    private MockObject&SymfonySession $sessionMock;
    private SymfonyTransaction $transactionMock;

    protected function setUp(): void
    {
        $this->driverSetupManagerMock = $this->createMock(DriverSetupManager::class);
        $this->driverFactoryMock = $this->createMock(SymfonyDriverFactory::class);
        $this->sessionMock = $this->createMock(SymfonySession::class);
        $this->transactionMock = $this->createMock(SymfonyTransaction::class);

        $this->sessionConfig = new SessionConfiguration();
        $this->transactionConfig = new TransactionConfiguration();

        $this->driverSetupManagerMock
            ->method('getDefaultAlias')
            ->willReturn('default');

        $this->client = new SymfonyClient(
            $this->driverSetupManagerMock,
            $this->sessionConfig,
            $this->transactionConfig,
            $this->driverFactoryMock
        );
    }

    public function testRunStatement():void
    {
        $statement = Statement::create('MATCH (n) RETURN n');
        $cypherMapMock = $this->createMock(CypherMap::class);
        $summarizedResultMock = $this->createMock(SummarizedResult::class);

        $this->sessionMock
            ->expects($this->once())
            ->method('runStatements')
            ->with([$statement], $this->transactionConfig)
            ->willReturn(new CypherList([$summarizedResultMock]));


        $reflection = new \ReflectionClass($this->client);
        $boundSessions = $reflection->getProperty('boundSessions');
        $boundSessions->setValue($this->client, ['default' => $this->sessionMock]);

        $result = $this->client->runStatement($statement);

        $this->assertInstanceOf(SummarizedResult::class, $result);
    }
    public function testWriteTransaction():void
    {
        $expectedResult = 'transaction success';
        $expectedResult = 'transaction success';

        $this->sessionMock
            ->expects($this->once())
            ->method('writeTransaction')
            ->willReturnCallback(function (callable $tsxHandler):mixed{
                return $tsxHandler($this->transactionMock);
            });

        $reflection = new \ReflectionClass($this->client);
        $boundSessions = $reflection->getProperty('boundSessions');
        $boundSessions->setValue($this->client, ['default' => $this->sessionMock]);

        $result = $this->client->writeTransaction(fn ($tsx) => 'transaction success');

        $this->assertEquals($expectedResult, $result);
    }


}
