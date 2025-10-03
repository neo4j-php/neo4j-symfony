<?php

namespace Neo4j\Neo4jBundle\Decorators;

use Laudis\Neo4j\Basic\Session;
use Laudis\Neo4j\Contracts\ConnectionPoolInterface;
use Laudis\Neo4j\Contracts\CypherSequence;
use Laudis\Neo4j\Contracts\SessionInterface;
use Laudis\Neo4j\Databags\Bookmark;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Enum\AccessMode;
use Laudis\Neo4j\Exception\Neo4jException;
use Laudis\Neo4j\Types\CypherList;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\Factories\SymfonyDriverFactory;

final class SymfonySession implements SessionInterface
{
    private const MAX_RETRIES = 3;
    private const ROLLBACK_CLASSIFICATIONS = ['ClientError', 'TransientError', 'DatabaseError'];

    public function __construct(
        private readonly Session $session,
        private readonly EventHandler $handler,
        private readonly SymfonyDriverFactory $factory,
        private readonly string $alias,
        private readonly string $schema,
        private readonly SessionConfiguration $config,
        private readonly ConnectionPoolInterface $pool,
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
        return $this->retryTransaction($tsxHandler, $config, read: false);
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
        return $this->retryTransaction($tsxHandler, $config, read: true);
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

    /**
     * Custom retry transaction logic to replace TransactionHelper.
     *
     * @template HandlerResult
     *
     * @param callable(SymfonyTransaction):HandlerResult $tsxHandler
     *
     * @return HandlerResult
     */
    private function retryTransaction(callable $tsxHandler, ?TransactionConfiguration $config, bool $read)
    {
        $attempt = 0;

        while (true) {
            ++$attempt;
            $transaction = null;

            try {
                $sessionConfig = $this->config->withAccessMode($read ? AccessMode::READ() : AccessMode::WRITE());
                $transaction = $this->startTransaction($config, $sessionConfig);

                $result = $tsxHandler($transaction);

                self::triggerLazyResult($result);
                $transaction->commit();

                return $result;
            } catch (Neo4jException $e) {
                if ($transaction && !in_array($e->getClassification(), self::ROLLBACK_CLASSIFICATIONS)) {
                    $transaction->rollback();
                }

                if ('NotALeader' === $e->getTitle()) {
                    $this->pool->close();
                } elseif ('TransientError' !== $e->getClassification()) {
                    throw $e;
                }

                if ($attempt >= self::MAX_RETRIES) {
                    throw $e;
                }

                usleep(100_000);
            }
        }
    }

    private static function triggerLazyResult(mixed $tbr): void
    {
        if ($tbr instanceof CypherSequence) {
            $tbr->preload();
        }
    }

    private function startTransaction(?TransactionConfiguration $config, SessionConfiguration $sessionConfig): SymfonyTransaction
    {
        return $this->factory->createTransaction(
            session: $this->session,
            config: $config,
            alias: $this->alias,
            schema: $this->schema
        );
    }
}
