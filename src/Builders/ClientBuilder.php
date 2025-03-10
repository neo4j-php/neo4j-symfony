<?php

namespace Neo4j\Neo4jBundle\Builders;

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\Common\DriverSetupManager;
use Laudis\Neo4j\Common\Uri;
use Laudis\Neo4j\Contracts\AuthenticateInterface;
use Laudis\Neo4j\Databags\DriverConfiguration;
use Laudis\Neo4j\Databags\DriverSetup;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Exception\UnsupportedScheme;
use Neo4j\Neo4jBundle\Decorators\SymfonyClient;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;

final class ClientBuilder
{
    public const SUPPORTED_SCHEMES = ['', 'bolt', 'bolt+s', 'bolt+ssc', 'neo4j', 'neo4j+s', 'neo4j+ssc', 'http', 'https'];

    /**
     * @psalm-mutation-free
     *
     * @param DriverSetupManager<mixed> $driverSetups
     */
    public function __construct(
        private SessionConfiguration $defaultSessionConfig,
        private TransactionConfiguration $defaultTransactionConfig,
        private DriverSetupManager $driverSetups,
        private readonly SymfonyDriverFactory $driverFactory,
    ) {
    }

    public function withDriver(string $alias, string $url, ?AuthenticateInterface $authentication = null, ?int $priority = 0): self
    {
        $uri = Uri::create($url);

        $authentication ??= Authenticate::fromUrl($uri, $this->driverSetups->getLogger());

        return $this->withParsedUrl($alias, $uri, $authentication, $priority ?? 0);
    }

    private function withParsedUrl(string $alias, Uri $uri, AuthenticateInterface $authentication, int $priority): self
    {
        $scheme = $uri->getScheme();

        if (!in_array($scheme, self::SUPPORTED_SCHEMES, true)) {
            throw UnsupportedScheme::make($scheme, self::SUPPORTED_SCHEMES);
        }

        $tbr = clone $this;
        $tbr->driverSetups = $this->driverSetups->withSetup(new DriverSetup($uri, $authentication), $alias, $priority);

        return $tbr;
    }

    public function withDefaultDriver(string $alias): self
    {
        $tbr = clone $this;
        $tbr->driverSetups = $this->driverSetups->withDefault($alias);

        return $tbr;
    }

    public function build(): SymfonyClient
    {
        return new SymfonyClient(
            driverSetups: $this->driverSetups,
            defaultSessionConfiguration: $this->defaultSessionConfig,
            defaultTransactionConfiguration: $this->defaultTransactionConfig,
            factory: $this->driverFactory
        );
    }

    public function withDefaultDriverConfiguration(DriverConfiguration $config): self
    {
        $tbr = clone $this;

        $tbr->driverSetups = $tbr->driverSetups->withDriverConfiguration($config);

        return $tbr;
    }

    public function withDefaultSessionConfiguration(SessionConfiguration $config): self
    {
        $tbr = clone $this;
        $tbr->defaultSessionConfig = $config;

        return $tbr;
    }

    public function withDefaultTransactionConfiguration(TransactionConfiguration $config): self
    {
        $tbr = clone $this;
        $tbr->defaultTransactionConfig = $config;

        return $tbr;
    }
}
