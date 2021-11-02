<?php

namespace Neo4j\Neo4jBundle\Tests\Functional;

use InvalidArgumentException;
use Laudis\Neo4j\Contracts\ClientInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BundleInitializationTest extends BaseTestCase
{
    public function testRegisterBundle(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();

        $this->assertTrue($container->has('neo4j.client'));
        $client = $container->get('neo4j.client');
        self::assertInstanceOf(ClientInterface::class, $client);
        $this->assertInstanceOf(ClientInterface::class, $client);

        $this->assertTrue($container->has(ClientInterface::class));
        $client = $container->get(ClientInterface::class);
        self::assertInstanceOf(ClientInterface::class, $client);
        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    public function testDefaultDsn(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();

        $client = $container->get('neo4j.client');
        $this->expectNotToPerformAssertions();
        $client->getDriver('default');
    }

    public function testDsn(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();

        $client = $container->get('neo4j.client');
        $this->expectNotToPerformAssertions();
        $client->getDriver('bolt');
    }

    public function testSecond(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();

        $client = $container->get('neo4j.client.other_client');
        self::assertInstanceOf(ClientInterface::class, $client);
        $client->getDriver('default');

        $this->expectException(InvalidArgumentException::class);
        $client->getDriver('bolt');
    }
}
