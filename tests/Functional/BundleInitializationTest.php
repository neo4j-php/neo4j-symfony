<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Functional;

use Laudis\Neo4j\Contracts\ClientInterface;
use Neo4j\Neo4jBundle\Tests\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testRegisterBundle(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->assertTrue($container->has('neo4j.client'));
        $client = $container->get('neo4j.client');
        self::assertInstanceOf(ClientInterface::class, $client);
        $this->assertInstanceOf(ClientInterface::class, $client);

        $this->assertTrue($container->has(ClientInterface::class));
        self::assertInstanceOf(ClientInterface::class, $client);
        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    public function testDefaultDsn(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $client = $container->get('neo4j.client');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot connect to any server on alias: neo4j_undefined_configs with Uris: ('bolt://localhost')");
        $client->getDriver('default');
    }

    public function testDsn(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot connect to any server on alias: neo4j_undefined_configs with Uris: ('bolt://localhost')");

        $container->get('neo4j.driver');
    }
}
