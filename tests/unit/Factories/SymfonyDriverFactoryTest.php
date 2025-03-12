<?php

namespace Neo4j\Neo4jBundle\Tests\Unit\Factories;

use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Basic\Session;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Enum\TransactionState;
use Neo4j\Neo4jBundle\Decorators\SymfonyDriver;
use Neo4j\Neo4jBundle\Decorators\SymfonySession;
use Neo4j\Neo4jBundle\Decorators\SymfonyTransaction;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

class SymfonyDriverFactoryTest extends TestCase
{
    private EventHandler $eventHandler;
    private ?UuidFactory $uuidFactory;
    private SymfonyDriverFactory $factory;

    protected function setUp(): void
    {
        $this->eventHandler = $this->createMock(EventHandler::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->factory = new SymfonyDriverFactory($this->eventHandler, $this->uuidFactory);
    }

    public function testCreateTransaction(): void
    {
        $session = $this->createMock(Session::class);
        $transactionConfig = $this->createMock(TransactionConfiguration::class);
        $alias = 'test_alias';
        $schema = 'test_schema';

        $symfonyTransaction = $this->createMock(SymfonyTransaction::class);

        $this->eventHandler
            ->expects($this->once())
            ->method('handleTransactionAction')
            ->with(TransactionState::ACTIVE)
            ->willReturn($symfonyTransaction);

        $transaction = $this->factory->createTransaction($session, $transactionConfig, $alias, $schema);

        $this->assertInstanceOf(SymfonyTransaction::class, $transaction);
    }

    public function testCreateSession(): void
    {
        $driver = $this->createMock(Driver::class);
        $sessionConfig = $this->createMock(SessionConfiguration::class);
        $alias = 'test_alias';
        $schema = 'test_schema';

        $driver->expects($this->once())
            ->method('createSession')
            ->with($sessionConfig)
            ->willReturn($this->createMock(Session::class));

        $session = $this->factory->createSession($driver, $sessionConfig, $alias, $schema);

        $this->assertInstanceOf(SymfonySession::class, $session);
    }

    public function testCreateDriver(): void
    {
        $driver = $this->createMock(Driver::class);
        $alias = 'test_alias';
        $schema = 'test_schema';

        $symfonyDriver = $this->factory->createDriver($driver, $alias, $schema);

        $this->assertInstanceOf(SymfonyDriver::class, $symfonyDriver);
    }

    public function testGenerateTransactionIdWithUuidFactory(): void
    {
        $uuid = $this->createMock(Uuid::class);
        $uuid->method('toRfc4122')->willReturn('test-uuid');

        $this->uuidFactory->method('create')->willReturn($uuid);

        $reflection = new \ReflectionClass(SymfonyDriverFactory::class);
        $method = $reflection->getMethod('generateTransactionId');

        $id = $method->invoke($this->factory);

        $this->assertSame('test-uuid', $id);
    }

    public function testGenerateTransactionIdWithoutUuidFactory(): void
    {
        $factory = new SymfonyDriverFactory($this->eventHandler, null);

        $reflection = new \ReflectionClass(SymfonyDriverFactory::class);
        $method = $reflection->getMethod('generateTransactionId');

        $id = $method->invoke($factory);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/', $id);
    }
}
