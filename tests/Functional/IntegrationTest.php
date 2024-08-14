<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Functional;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Neo4j\Neo4jDriver;
use Neo4j\Neo4jBundle\Tests\App\TestKernel;
use Psr\Http\Message\UriInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::bootKernel();
    }

    public function testClient(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->assertTrue($container->has('neo4j.client'));
        $client = $container->get('neo4j.client');
        $this->assertInstanceOf(ClientInterface::class, $client);

        $this->assertTrue($container->has(ClientInterface::class));
        $this->assertInstanceOf(ClientInterface::class, $client);

        $this->assertSame($client, $container->get('neo4j.client'));
    }

    public function testDriver(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->assertTrue($container->has('neo4j.driver'));
        $driver = $container->get('neo4j.driver');
        $this->assertInstanceOf(DriverInterface::class, $driver);

        $this->assertTrue($container->has(DriverInterface::class));
        $this->assertInstanceOf(DriverInterface::class, $driver);

        $this->assertSame($driver, $container->get('neo4j.driver'));
    }

    public function testDefaultDsn(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /**
         * @var ClientInterface $client
         */
        $client = $container->get('neo4j.client');
        /**
         * @var Neo4jDriver $driver
         */
        $driver = $client->getDriver('default');
        $reflection = new \ReflectionClass($driver);
        $property = $reflection->getProperty('parsedUrl');
        /**
         * @var UriInterface $uri
         */
        $uri = $property->getValue($driver);

        $this->assertSame($uri->getScheme(), 'neo4j');
    }

    public function testDsn(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches(
            "/Cannot connect to any server on alias: neo4j_undefined_configs with Uris: \('bolt:\/\/(localhost|localhostt)'\)/"
        );

        /**
         * @var ClientInterface $client
         */
        $client = $container->get('neo4j.client');
        $client->getDriver('neo4j_undefined_configs');
    }
}
