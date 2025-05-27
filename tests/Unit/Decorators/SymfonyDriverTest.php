<?php

namespace Neo4j\Neo4jBundle\Tests\Decorators;

use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Neo4j\Neo4jBundle\Decorators\SymfonyDriver;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;
use Neo4j\Neo4jBundle\Decorators\SymfonySession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SymfonyDriverTest extends TestCase
{
    private MockObject&Driver $driverMock;
    private MockObject&SymfonyDriverFactory $factoryMock;
    private SymfonyDriver $symfonyDriver;
    private string $alias = 'default';
    private string $schema = 'neo4j';

    protected function setUp(): void
    {
        $this->driverMock = $this->createMock(Driver::class);
        $this->factoryMock = $this->createMock(SymfonyDriverFactory::class);

        $this->symfonyDriver = new SymfonyDriver(
            $this->driverMock,
            $this->factoryMock,
            $this->alias,
            $this->schema
        );
    }

    public function testCreateSession():void
    {
        $sessionMock = $this->createMock(SymfonySession::class);
        $configMock = $this->createMock(SessionConfiguration::class);

        $this->factoryMock
            ->expects($this->once())
            ->method('createSession')
            ->with($this->driverMock, $configMock, $this->alias, $this->schema)
            ->willReturn($sessionMock);

        $session = $this->symfonyDriver->createSession($configMock);
        $this->assertInstanceOf(SymfonySession::class, $session);
    }

    public function testVerifyConnectivity():void
    {
        $this->driverMock
            ->expects($this->once())
            ->method('verifyConnectivity')
            ->willReturn(true);

        $this->assertTrue($this->symfonyDriver->verifyConnectivity());
    }

    public function testCloseConnections():void
    {
        $this->driverMock
            ->expects($this->once())
            ->method('closeConnections');

        $this->symfonyDriver->closeConnections();
    }
}
