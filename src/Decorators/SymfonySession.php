<?php

namespace Neo4j\Neo4jBundle\Decorators;

use Laudis\Neo4j\Basic\Session;
use Laudis\Neo4j\Common\TransactionHelper;
use Laudis\Neo4j\Contracts\SessionInterface;
use Laudis\Neo4j\Databags\Bookmark;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Types\CypherList;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;

final class SymfonySession implements SessionInterface
{
    public function __construct(
        private readonly Session $session,
        private readonly EventHandler $handler,
        private readonly SymfonyDriverFactory $factory,
        private readonly string $alias,
        private readonly string $schema,
    ) {
    }

    #[\Override]
    public function runStatements(iterable $statements, ?TransactionConfiguration $config = null): CypherList
    {
        $tbr = [];
        foreach ($statements as $statement) {
            $tbr[] = $this->runStatement($statement);
        }

        return CypherList::fromIterable($tbr);
    }

    #[\Override]
    public function runStatement(Statement $statement, ?TransactionConfiguration $config = null): SummarizedResult
    {
        return $this->handler->handleQuery(
            runHandler: fn (Statement $statement) => $this->session->runStatement($statement),
            statement: $statement,
            alias: $this->alias,
            scheme: $this->schema,
            transactionId: null
        );
    }

    #[\Override]
    public function run(string $statement, iterable $parameters = [], ?TransactionConfiguration $config = null): SummarizedResult
    {
        return $this->runStatement(new Statement($statement, $parameters));
    }

    #[\Override]
    public function beginTransaction(?iterable $statements = null, ?TransactionConfiguration $config = null): SymfonyTransaction
    {
        return $this->factory->createTransaction(
            session: $this->session,
            config: $config,
            alias: $this->alias,
            schema: $this->schema
        );
    }

    /**
     * @template HandlerResult
     *
     * @param callable(SymfonyTransaction):HandlerResult $tsxHandler
     *
     * @return HandlerResult
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    #[\Override]
    public function writeTransaction(callable $tsxHandler, ?TransactionConfiguration $config = null)
    {
        return TransactionHelper::retry(
            fn () => $this->beginTransaction(config: $config),
            $tsxHandler
        );
    }

    /**
     * @template HandlerResult
     *
     * @param callable(SymfonyTransaction):HandlerResult $tsxHandler
     *
     * @return HandlerResult
     */
    #[\Override]
    public function readTransaction(callable $tsxHandler, ?TransactionConfiguration $config = null)
    {
        // TODO: create read transaction here.
        return $this->writeTransaction($tsxHandler, $config);
    }

    /**
     * @template HandlerResult
     *
     * @param callable(SymfonyTransaction):HandlerResult $tsxHandler
     *
     * @return HandlerResult
     */
    #[\Override]
    public function transaction(callable $tsxHandler, ?TransactionConfiguration $config = null)
    {
        return $this->writeTransaction($tsxHandler, $config);
    }

    #[\Override]
    public function getLastBookmark(): Bookmark
    {
        return $this->session->getLastBookmark();
    }
}
