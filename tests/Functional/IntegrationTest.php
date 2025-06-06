<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Functional;

use Laudis\Neo4j\Common\DriverSetupManager;
use Laudis\Neo4j\Common\SingleThreadedSemaphore;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Databags\ConnectionRequestData;
use Laudis\Neo4j\Databags\DriverSetup;
use Laudis\Neo4j\Databags\SslConfiguration;
use Laudis\Neo4j\Enum\SslMode;
use Laudis\Neo4j\Neo4j\Neo4jConnectionPool;
use Laudis\Neo4j\Neo4j\Neo4jDriver;
use Neo4j\Neo4jBundle\Decorators\SymfonyClient;
use Neo4j\Neo4jBundle\Tests\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @psalm-suppress InternalMethod
 * @psalm-suppress UndefinedInterfaceMethod
 */
final class IntegrationTest extends KernelTestCase
{
    #[\Override]
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    #[\Override]
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

        $driver = $this->getPrivateProperty($driver, 'driver');
        $driver = $this->getPrivateProperty($driver, 'driver');

        $uri = $this->getPrivateProperty($driver, 'parsedUrl');

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

    public function testDriverAuthentication(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /**
         * @var ClientInterface $client
         */
        $client = $container->get('neo4j.client');
        /** @var Neo4jDriver $driver */
        $driver = $client->getDriver('neo4j-auth');

        $driver = $this->getPrivateProperty($driver, 'driver');
        $driver = $this->getPrivateProperty($driver, 'driver');

        /** @var Neo4jConnectionPool $pool */
        $pool = $this->getPrivateProperty($driver, 'pool');
        /** @var ConnectionRequestData $data */
        $data = $this->getPrivateProperty($pool, 'data');
        $auth = $data->getAuth();
        /** @var string $username */
        $username = $this->getPrivateProperty($auth, 'username');
        /** @var string $password */
        $password = $this->getPrivateProperty($auth, 'password');

        $this->assertSame($username, 'neo4j');
        $this->assertSame($password, 'testtest');
    }

    public function testDefaultDriverConfig(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /**
         * @var ClientInterface $client
         */
        $client = $container->get('neo4j.client');
        /** @var Neo4jDriver $driver */
        $driver = $client->getDriver('default');

        $driver = $this->getPrivateProperty($driver, 'driver');
        $driver = $this->getPrivateProperty($driver, 'driver');

        /** @var Neo4jConnectionPool $pool */
        $pool = $this->getPrivateProperty($driver, 'pool');
        /** @var SingleThreadedSemaphore $semaphore */
        $semaphore = $this->getPrivateProperty($pool, 'semaphore');
        /** @var int $max */
        $max = $this->getPrivateProperty($semaphore, 'max');

        // default_driver_config.pool_size
        $this->assertSame($max, 256);

        /** @var ConnectionRequestData $data */
        $data = $this->getPrivateProperty($pool, 'data');

        $this->assertSame($data->getUserAgent(), 'Neo4j Symfony Bundle/testing');

        /** @var SslConfiguration $sslConfig */
        $sslConfig = $this->getPrivateProperty($data, 'config');
        /** @var SslMode $sslMode */
        $sslMode = $this->getPrivateProperty($sslConfig, 'mode');
        /** @var bool $verifyPeer */
        $verifyPeer = $this->getPrivateProperty($sslConfig, 'verifyPeer');

        $this->assertSame($sslMode, SslMode::DISABLE());
        $this->assertFalse($verifyPeer);
    }

    public function testDefaultSessionConfig(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /**
         * @var ClientInterface $client
         */
        $client = $container->get('neo4j.client');
        $sessionConfig = $client->getDefaultSessionConfiguration();

        $this->assertSame($sessionConfig->getFetchSize(), 999);
    }

    public function testDefaultTransactionConfig(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /**
         * @var ClientInterface $client
         */
        $client = $container->get('neo4j.client');
        $transactionConfig = $client->getDefaultTransactionConfiguration();

        $this->assertSame($transactionConfig->getTimeout(), 40.0);
    }

    public function testDriverAndSessionTags(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->assertTrue($container->has('neo4j.driver.neo4j-simple'));
        $this->assertTrue($container->has('neo4j.driver.neo4j-test'));

        $this->assertTrue($container->has('neo4j.session.neo4j-simple'));
        $this->assertTrue($container->has('neo4j.session.neo4j-test'));
    }

    public function testPriority(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /**
         * @var ClientInterface $client
         */
        $client = $container->get('neo4j.client');
        /** @var DriverSetupManager $drivers */
        $drivers = $this->getPrivateProperty($client, 'driverSetups');
        /** @var array<\SplPriorityQueue<int, DriverSetup>> $fallbackDriverQueue */
        $driverSetups = $this->getPrivateProperty($drivers, 'driverSetups');
        /** @var \SplPriorityQueue<int, DriverSetup> $fallbackDriverQueue */
        $fallbackDriverQueue = $driverSetups['neo4j-fallback-mechanism'];
        $fallbackDriverQueue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
        /** @var array{data: DriverSetup, priority: int} $extractedValue */
        $extractedValue = $fallbackDriverQueue->extract();

        $this->assertSame($extractedValue['priority'], 1000);
    }

    public function testDefaultLogLevel(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /**
         * @var SymfonyClient $client
         */
        $client = $container->get('neo4j.client');
        /** @var Neo4jDriver $driver */
        $driver = $client->getDriver('default');

        $driver = $this->getPrivateProperty($driver, 'driver');
        $driver = $this->getPrivateProperty($driver, 'driver');

        /** @var Neo4jConnectionPool $pool */
        $pool = $this->getPrivateProperty($driver, 'pool');
        $level = $pool->getLogger()?->getLevel();

        $this->assertSame('warning', $level);
    }

    private function getPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);

        return $property->getValue($object);
    }
}
