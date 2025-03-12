<?php

namespace Neo4j\Neo4jBundle\Tests\Unit\Factories;

use Neo4j\Neo4jBundle\Factories\StopwatchEventNameFactory;
use Laudis\Neo4j\Enum\TransactionState;
use PHPUnit\Framework\TestCase;

class StopwatchEventNameFactoryTest extends TestCase
{
    private StopwatchEventNameFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new StopwatchEventNameFactory();
    }

    public function testCreateQueryEventNameWithoutTransaction(): void
    {
        $alias = 'database1';
        $transactionId = null;
        $expected = 'neo4j.database1.query';

        $this->assertSame($expected, $this->factory->createQueryEventName($alias, $transactionId));
    }

    public function testCreateQueryEventNameWithTransaction(): void
    {
        $alias = 'database1';
        $transactionId = 'tx123';
        $expected = 'neo4j.database1.transaction.tx123.query';

        $this->assertSame($expected, $this->factory->createQueryEventName($alias, $transactionId));
    }

    /**
     * @dataProvider transactionEventNameProvider
     */
    public function testCreateTransactionEventName(TransactionState $state, string $expectedAction): void
    {
        $alias = 'database1';
        $transactionId = 'tx123';
        $expected = sprintf('neo4j.%s.transaction.%s.%s', $alias, $transactionId, $expectedAction);

        $this->assertSame($expected, $this->factory->createTransactionEventName($alias, $transactionId, $state));
    }

    public function transactionEventNameProvider(): array
    {
        return [
            [TransactionState::COMMITTED, 'commit'],
            [TransactionState::ACTIVE, 'begin'],
            [TransactionState::ROLLED_BACK, 'rollback'],
            [TransactionState::TERMINATED, 'error'],
        ];
    }
}
