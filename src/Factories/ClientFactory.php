<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Factories;

use InvalidArgumentException;
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\Common\Uri;
use Laudis\Neo4j\Contracts\AuthenticateInterface;
use Laudis\Neo4j\Databags\DriverConfiguration;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\SslConfiguration;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Enum\AccessMode;
use Laudis\Neo4j\Enum\SslMode;
use Neo4j\Neo4jBundle\Builders\ClientBuilder;
use Neo4j\Neo4jBundle\Decorators\SymfonyClient;
use Neo4j\Neo4jBundle\DependencyInjection\Configuration;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type SessionConfigArray from Configuration
 * @psalm-import-type DriverConfigArray from Configuration
 * @psalm-import-type TransactionConfigArray from Configuration
 * @psalm-import-type DriverRegistrationArray from Configuration
 * @psalm-import-type DriverAuthenticationArray from Configuration
 * @psalm-import-type SslConfigArray from Configuration
 */
final class ClientFactory
{
    /**
     * @param DriverConfigArray|null        $driverConfig
     * @param SessionConfigArray|null       $sessionConfig
     * @param TransactionConfigArray|null   $transactionConfig
     * @param list<DriverRegistrationArray> $connections
     */
    public function __construct(
        private readonly ?array $driverConfig,
        private readonly ?array $sessionConfig,
        private readonly ?array $transactionConfig,
        private readonly array $connections,
        private readonly ?string $defaultDriver,
        private readonly ?string $logLevel,
        private readonly ?LoggerInterface $logger,
        private ClientBuilder $builder,
    ) {
    }

    public function create(): SymfonyClient
    {
        if (null !== $this->driverConfig) {
            $this->builder = $this->builder->withDefaultDriverConfiguration(
                $this->makeDriverConfig($this->logLevel, $this->logger)
            );
        }

        if (null !== $this->sessionConfig) {
            $this->builder = $this->builder->withDefaultSessionConfiguration($this->makeSessionConfig());
        }

        if (null !== $this->transactionConfig) {
            $this->builder = $this->builder->withDefaultTransactionConfiguration($this->makeTransactionConfig());
        }

        foreach ($this->connections as $connection) {
            $this->builder = $this->builder->withDriver(
                $connection['alias'],
                $connection['dsn'],
                $this->createAuth($connection['authentication'] ?? null, $connection['dsn']),
                $connection['priority'] ?? null
            );
        }

        if (null !== $this->defaultDriver) {
            $this->builder = $this->builder->withDefaultDriver($this->defaultDriver);
        }

        return $this->builder->build();
    }

    private function makeDriverConfig(?string $logLevel = null, ?LoggerInterface $logger = null): DriverConfiguration
    {
        return new DriverConfiguration(
            userAgent: $this->driverConfig['user_agent'] ?? null,
            sslConfig: $this->makeSslConfig($this->driverConfig['ssl'] ?? null),
            maxPoolSize: $this->driverConfig['pool_size'] ?? null,
            cache: null,
            acquireConnectionTimeout: $this->driverConfig['acquire_connection_timeout'] ?? null,
            semaphore: null,
            logLevel: $logLevel,
            logger: $logger,
        );
    }

    private function makeSessionConfig(): SessionConfiguration
    {
        return new SessionConfiguration(
            database: $this->sessionConfig['database'] ?? null,
            fetchSize: $this->sessionConfig['fetch_size'] ?? null,
            accessMode: match ($this->sessionConfig['access_mode'] ?? null) {
                'write', null => AccessMode::WRITE(),
                'read' => AccessMode::READ(),
            },
        );
    }

    private function makeTransactionConfig(): TransactionConfiguration
    {
        return new TransactionConfiguration(
            timeout: $this->transactionConfig['timeout'] ?? null
        );
    }

    /**
     * @param DriverAuthenticationArray|null $auth
     */
    private function createAuth(?array $auth, string $dsn): AuthenticateInterface
    {
        if (null === $auth) {
            return Authenticate::fromUrl(Uri::create($dsn));
        }

        return match ($auth['type'] ?? null) {
            'basic' => Authenticate::basic(
                $auth['username'] ?? throw new InvalidArgumentException('Missing username for basic authentication'),
                $auth['password'] ?? throw new InvalidArgumentException('Missing password for basic authentication')
            ),
            'kerberos' => Authenticate::kerberos(
                $auth['token'] ?? throw new InvalidArgumentException('Missing token for kerberos authentication')
            ),
            'dsn', null => Authenticate::fromUrl(Uri::create($dsn)),
            'none' => Authenticate::disabled(),
            'oid' => Authenticate::oidc(
                $auth['token'] ?? throw new InvalidArgumentException('Missing token for oid authentication')
            ),
        };
    }

    /**
     * @param SslConfigArray|null $ssl
     */
    private function makeSslConfig(?array $ssl): SslConfiguration
    {
        if (null === $ssl) {
            return new SslConfiguration(
                mode: SslMode::DISABLE(),
                verifyPeer: false,
            );
        }

        return new SslConfiguration(
            mode: match ($ssl['mode'] ?? null) {
                null, 'disable' => SslMode::DISABLE(),
                'enable' => SslMode::ENABLE(),
                'from_url' => SslMode::FROM_URL(),
                'enable_with_self_signed' => SslMode::ENABLE_WITH_SELF_SIGNED(),
            },
            verifyPeer: !(($ssl['verify_peer'] ?? true) === false),
        );
    }
}
