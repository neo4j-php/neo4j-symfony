<?php

namespace Neo4j\Neo4jBundle\Factories;

use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Basic\Session;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Enum\TransactionState;
use Neo4j\Neo4jBundle\Decorators\SymfonyDriver;
use Neo4j\Neo4jBundle\Decorators\SymfonySession;
use Neo4j\Neo4jBundle\Decorators\SymfonyTransaction;
use Neo4j\Neo4jBundle\EventHandler;
use Symfony\Component\Uid\Factory\UuidFactory;

final class SymfonyDriverFactory
{
    public function __construct(
        private readonly EventHandler $handler,
        private readonly ?UuidFactory $uuidFactory,
    ) {
    }

    public function createTransaction(Session $session, ?TransactionConfiguration $config, string $alias, string $schema): SymfonyTransaction
    {
        $tranactionId = $this->generateTransactionId();

        $handler = fn (): SymfonyTransaction => new SymfonyTransaction(
            tsx: $session->beginTransaction(config: $config),
            handler: $this->handler,
            alias: $alias,
            scheme: $schema,
            transactionId: $tranactionId
        );

        return $this->handler->handleTransactionAction(
            nextTransactionState: TransactionState::ACTIVE,
            transactionId: $tranactionId,
            runHandler: $handler,
            alias: $alias,
            scheme: $schema,
        );
    }

    public function createSession(
        Driver $driver,
        ?SessionConfiguration $config,
        string $alias,
        string $schema,
    ): SymfonySession {
        return new SymfonySession(
            session: $driver->createSession($config),
            handler: $this->handler,
            factory: $this,
            alias: $alias,
            schema: $schema,
        );
    }

    public function createDriver(
        Driver $driver,
        string $alias,
        string $schema,
    ): SymfonyDriver {
        return new SymfonyDriver(
            $driver,
            $this,
            $alias,
            $schema,
        );
    }

    private function generateTransactionId(): string
    {
        if ($this->uuidFactory) {
            return $this->uuidFactory->create()->toRfc4122();
        }

        $data = random_bytes(16);

        // Set the version to 4 (UUID v4)
        $data[6] = chr((ord($data[6]) & 0x0F) | 0x40);

        // Set the variant to RFC 4122 (10xx)
        $data[8] = chr((ord($data[8]) & 0x3F) | 0x80);

        // Format the UUID as 8-4-4-4-12 hexadecimal characters
        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }
}
