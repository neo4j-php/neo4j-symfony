<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle;

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Common\Uri;
use Laudis\Neo4j\Contracts\AuthenticateInterface;
use Laudis\Neo4j\Databags\DriverConfiguration;
use Laudis\Neo4j\Databags\HttpPsrBindings;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\SslConfiguration;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Enum\AccessMode;
use Laudis\Neo4j\Enum\SslMode;
use Laudis\Neo4j\Types\CypherMap;
use Neo4j\Neo4jBundle\DependencyInjection\Configuration;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @psalm-import-type SessionConfigArray from Configuration
 * @psalm-import-type DriverConfigArray from Configuration
 * @psalm-import-type TransactionConfigArray from Configuration
 * @psalm-import-type DriverRegistrationArray from Configuration
 * @psalm-import-type DriverAuthenticationArray from Configuration
 * @psalm-import-type SslConfigArray from Configuration
 */
class ClientFactory
{
    /**
     * @param DriverConfigArray|null        $driverConfig
     * @param SessionConfigArray|null       $sessionConfiguration
     * @param TransactionConfigArray|null   $transactionConfiguration
     * @param list<DriverRegistrationArray> $connections
     */
    public function __construct(
        private EventHandler $eventHandler,
        private array|null $driverConfig,
        private array|null $sessionConfiguration,
        private array|null $transactionConfiguration,
        private array $connections,
        private string|null $defaultDriver,
        private ClientInterface|null $client,
        private StreamFactoryInterface|null $streamFactory,
        private RequestFactoryInterface|null $requestFactory,
    ) {
    }

    public function create(): SymfonyClient
    {
        /** @var ClientBuilder<SummarizedResult<CypherMap>> $builder */
        $builder = ClientBuilder::create();

        if ($this->driverConfig) {
            $builder = $builder->withDefaultDriverConfiguration($this->makeDriverConfig());
        }

        if ($this->sessionConfiguration) {
            $builder = $builder->withDefaultSessionConfiguration($this->makeSessionConfig());
        }

        if ($this->transactionConfiguration) {
            $builder = $builder->withDefaultTransactionConfiguration($this->makeTransactionConfig());
        }

        foreach ($this->connections as $connection) {
            $builder = $builder->withDriver(
                $connection['alias'],
                $connection['dsn'],
                $this->createAuth($connection['authentication'] ?? null, $connection['dsn']),
                $connection['priority'] ?? null
            );
        }

        if ($this->defaultDriver) {
            $builder = $builder->withDefaultDriver($this->defaultDriver);
        }

        return new SymfonyClient($builder->build(), $this->eventHandler);
    }

    private function makeDriverConfig(): DriverConfiguration
    {
        $config = new DriverConfiguration(
            userAgent: $this->driverConfig['user_agent'] ?? null,
            httpPsrBindings: null,
            sslConfig: $this->makeSslConfig($this->driverConfig['ssl'] ?? null),
            maxPoolSize: $this->driverConfig['pool_size'] ?? null,
            cache: null,
            acquireConnectionTimeout: $this->driverConfig['acquire_connection_timeout'] ?? null,
            semaphore: null,
        );

        $bindings = new HttpPsrBindings();
        if ($this->client) {
            $config = $config->withHttpPsrBindings($bindings->withClient($this->client));
        }

        if ($this->streamFactory) {
            $config = $config->withHttpPsrBindings($bindings->withStreamFactory($this->streamFactory));
        }

        if ($this->requestFactory) {
            $config = $config->withHttpPsrBindings($bindings->withRequestFactory($this->requestFactory));
        }

        return $config;
    }

    private function makeSessionConfig(): SessionConfiguration
    {
        return new SessionConfiguration(
            database: $this->sessionConfiguration['database'] ?? null,
            fetchSize: $this->sessionConfiguration['fetch_size'] ?? null,
            accessMode: match ($this->sessionConfiguration['access_mode'] ?? null) {
                'write', null => AccessMode::WRITE(),
                'read' => AccessMode::READ(),
            },
        );
    }

    private function makeTransactionConfig(): TransactionConfiguration
    {
        return new TransactionConfiguration(
            timeout: $this->transactionConfiguration['timeout'] ?? null
        );
    }

    /**
     * @param DriverAuthenticationArray|null $auth
     */
    private function createAuth(array|null $auth, string $dsn): AuthenticateInterface
    {
        if (null === $auth) {
            return Authenticate::fromUrl(Uri::create($dsn));
        }

        return match ($auth['type'] ?? null) {
            'basic' => Authenticate::basic(
                $auth['username'] ?? throw new \InvalidArgumentException('Missing username for basic authentication'),
                $auth['password'] ?? throw new \InvalidArgumentException('Missing password for basic authentication')
            ),
            'kerberos' => Authenticate::kerberos($auth['token'] ?? throw new \InvalidArgumentException('Missing token for kerberos authentication')),
            'dsn', null => Authenticate::fromUrl(Uri::create($dsn)),
            'none' => Authenticate::disabled(),
            'oid' => Authenticate::oidc($auth['token'] ?? throw new \InvalidArgumentException('Missing token for oid authentication')),
        };
    }

    /**
     * @param SslConfigArray|null $ssl
     */
    private function makeSslConfig(array|null $ssl): SslConfiguration
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
