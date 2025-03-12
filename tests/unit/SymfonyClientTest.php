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
use PHPUnit\Framework\TestCase;

class SymfonyClientTest extends TestCase
{
    private SymfonyClient $client;
    private $driverSetupManagerMock;
    private SessionConfiguration $sessionConfig;
    private TransactionConfiguration $transactionConfig;
    private $driverFactoryMock;
    private $sessionMock;
    private $transactionMock;

    protected function setUp(): void
    {
        // Mocks
        $this->driverSetupManagerMock = $this->createMock(DriverSetupManager::class);
        $this->driverFactoryMock = $this->createMock(SymfonyDriverFactory::class);
        $this->sessionMock = $this->createMock(SymfonySession::class);
        $this->transactionMock = $this->createMock(SymfonyTransaction::class);

        // Use real instances for final classes
        $this->sessionConfig = new SessionConfiguration();
        $this->transactionConfig = new TransactionConfiguration();

        // Setup default alias return value
        $this->driverSetupManagerMock
            ->method('getDefaultAlias')
            ->willReturn('default');

        // Create SymfonyClient instance
        $this->client = new SymfonyClient(
            $this->driverSetupManagerMock,
            $this->sessionConfig,
            $this->transactionConfig,
            $this->driverFactoryMock
        );
    }

    public function testRunStatement()
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
    public function testWriteTransaction()
    {
        $expectedResult = 'transaction success';

        $this->sessionMock
            ->expects($this->once())
            ->method('writeTransaction')
            ->willReturnCallback(function ($tsxHandler) {
                return $tsxHandler($this->transactionMock);
            });

        $reflection = new \ReflectionClass($this->client);
        $boundSessions = $reflection->getProperty('boundSessions');
        $boundSessions->setValue($this->client, ['default' => $this->sessionMock]);

        $result = $this->client->writeTransaction(fn ($tsx) => 'transaction success');

        $this->assertEquals($expectedResult, $result);
    }


}
