<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Decorators;

use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Common\DriverSetupManager;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\SessionInterface;
use Laudis\Neo4j\Contracts\UnmanagedTransactionInterface;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Enum\AccessMode;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;

/**
 * A collection of drivers with methods to run queries though them.
 *
 * @implements ClientInterface<SummarizedResult<CypherMap>>
 *
 * @psalm-external-mutation-free
 *
 * @psalm-suppress ImpureMethodCall
 */
class SymfonyClient implements ClientInterface
{
    /**
     * @var array<string, list<SymfonyTransaction>>
     */
    private array $boundTransactions = [];

    /**
     * @var array<string, SymfonySession>
     */
    private array $boundSessions = [];

    /**
     * @psalm-mutation-free
     *
     * @param DriverSetupManager<mixed> $driverSetups
     */
    public function __construct(
        private readonly DriverSetupManager $driverSetups,
        private readonly SessionConfiguration $defaultSessionConfiguration,
        private readonly TransactionConfiguration $defaultTransactionConfiguration,
        private readonly SymfonyDriverFactory $factory,
    ) {
    }

    public function getDefaultSessionConfiguration(): SessionConfiguration
    {
        return $this->defaultSessionConfiguration;
    }

    public function getDefaultTransactionConfiguration(): TransactionConfiguration
    {
        return $this->defaultTransactionConfiguration;
    }

    public function run(string $statement, iterable $parameters = [], ?string $alias = null): SummarizedResult
    {
        return $this->runStatement(Statement::create($statement, $parameters), $alias);
    }

    public function runStatement(Statement $statement, ?string $alias = null): SummarizedResult
    {
        return $this->runStatements([$statement], $alias)->first();
    }

    private function getRunner(?string $alias = null): SymfonyTransaction|SymfonySession
    {
        $alias ??= $this->driverSetups->getDefaultAlias();

        if (
            array_key_exists($alias, $this->boundTransactions)
            && count($this->boundTransactions[$alias]) > 0
        ) {
            return $this->boundTransactions[$alias][array_key_last($this->boundTransactions[$alias])];
        }

        return $this->getSession($alias);
    }

    private function getSession(?string $alias = null): SymfonySession
    {
        $alias ??= $this->driverSetups->getDefaultAlias();

        if (array_key_exists($alias, $this->boundSessions)) {
            return $this->boundSessions[$alias];
        }

        return $this->boundSessions[$alias] = $this->startSession($alias, $this->defaultSessionConfiguration);
    }

    public function runStatements(iterable $statements, ?string $alias = null): CypherList
    {
        $runner = $this->getRunner($alias);
        if ($runner instanceof SessionInterface) {
            return $runner->runStatements($statements, $this->defaultTransactionConfiguration);
        }

        return $runner->runStatements($statements);
    }

    public function beginTransaction(?iterable $statements = null, ?string $alias = null, ?TransactionConfiguration $config = null): SymfonyTransaction
    {
        $session = $this->getSession($alias);
        $config = $this->getTsxConfig($config);

        return $session->beginTransaction($statements, $config);
    }

    public function getDriver(?string $alias): SymfonyDriver
    {
        return $this->factory->createDriver(
            new Driver($this->driverSetups->getDriver($this->defaultSessionConfiguration, $alias)),
            $alias ?? $this->driverSetups->getDefaultAlias(),
            '',
        );
    }

    private function startSession(?string $alias, SessionConfiguration $configuration): SymfonySession
    {
        return $this->factory->createSession(
            new Driver($this->driverSetups->getDriver($this->defaultSessionConfiguration, $alias)),
            $configuration,
            $alias ?? $this->driverSetups->getDefaultAlias(),
            '',
        );
    }

    /**
     * @template HandlerResult
     *
     * @param callable(SymfonyTransaction):HandlerResult $tsxHandler
     *
     * @return HandlerResult
     */
    public function writeTransaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfiguration $config = null): mixed
    {
        if ($this->defaultSessionConfiguration->getAccessMode() === AccessMode::WRITE()) {
            $session = $this->getSession($alias);
        } else {
            $sessionConfig = $this->defaultSessionConfiguration->withAccessMode(AccessMode::WRITE());
            $session = $this->startSession($alias, $sessionConfig);
        }

        return $session->writeTransaction($tsxHandler, $this->getTsxConfig($config));
    }

    /**
     * @template HandlerResult
     *
     * @param callable(SymfonyTransaction):HandlerResult $tsxHandler
     *
     * @return HandlerResult
     */
    public function readTransaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfiguration $config = null): mixed
    {
        if ($this->defaultSessionConfiguration->getAccessMode() === AccessMode::READ()) {
            $session = $this->getSession($alias);
        } else {
            $sessionConfig = $this->defaultSessionConfiguration->withAccessMode(AccessMode::WRITE());
            $session = $this->startSession($alias, $sessionConfig);
        }

        return $session->readTransaction($tsxHandler, $this->getTsxConfig($config));
    }

    /**
     * @template HandlerResult
     *
     * @param callable(SymfonyTransaction):HandlerResult $tsxHandler
     *
     * @return HandlerResult
     */
    public function transaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfiguration $config = null)
    {
        return $this->writeTransaction($tsxHandler, $alias, $config);
    }

    public function verifyConnectivity(?string $driver = null): bool
    {
        return $this->driverSetups->verifyConnectivity($this->defaultSessionConfiguration, $driver);
    }

    public function hasDriver(string $alias): bool
    {
        return $this->driverSetups->hasDriver($alias);
    }

    public function bindTransaction(?string $alias = null, ?TransactionConfiguration $config = null): void
    {
        $alias ??= $this->driverSetups->getDefaultAlias();

        $this->boundTransactions[$alias] ??= [];
        $this->boundTransactions[$alias][] = $this->beginTransaction(null, $alias, $config);
    }

    public function rollbackBoundTransaction(?string $alias = null, int $depth = 1): void
    {
        $this->popTransactions(static fn (SymfonyTransaction $tsx) => $tsx->rollback(), $alias, $depth);
    }

    /**
     * @param callable(SymfonyTransaction): void $handler
     *
     * @psalm-suppress ImpureFunctionCall
     */
    private function popTransactions(callable $handler, ?string $alias = null, int $depth = 1): void
    {
        $alias ??= $this->driverSetups->getDefaultAlias();

        if (!array_key_exists($alias, $this->boundTransactions)) {
            return;
        }

        while (0 !== count($this->boundTransactions[$alias]) && 0 !== $depth) {
            $tsx = array_pop($this->boundTransactions[$alias]);
            $handler($tsx);
            --$depth;
        }
    }

    public function commitBoundTransaction(?string $alias = null, int $depth = 1): void
    {
        $this->popTransactions(static fn (UnmanagedTransactionInterface $tsx) => $tsx->commit(), $alias, $depth);
    }

    private function getTsxConfig(?TransactionConfiguration $config): TransactionConfiguration
    {
        if (null !== $config) {
            return $this->defaultTransactionConfiguration->merge($config);
        }

        return $this->defaultTransactionConfiguration;
    }
}
