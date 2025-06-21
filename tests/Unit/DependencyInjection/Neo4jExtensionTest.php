<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit\DependencyInjection;

use Neo4j\Neo4jBundle\DependencyInjection\Neo4jExtension;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;


class Neo4jExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extension = new Neo4jExtension();
        $configs = [
            'default_driver' => 'neo4j_default',
            'min_log_level' => 'error',
            'drivers' => [
                ['alias' => 'default', 'dsn' => 'bolt://localhost:7687', 'profiling' => null],
            ],
        ];

        $extension->load([$configs], $container);

        $this->assertTrue($container->hasDefinition('neo4j.event_handler'));
        $this->assertTrue($container->hasDefinition('neo4j.client_factory'));
        $this->assertTrue($container->hasDefinition('neo4j.driver.default'));

        // Check event handler argument
        $eventHandlerDefinition = $container->getDefinition('neo4j.event_handler');
        $this->assertEquals('neo4j_default', $eventHandlerDefinition->getArgument(1));

        // Check client factory arguments
        $clientFactoryDefinition = $container->getDefinition('neo4j.client_factory');
        $this->assertEquals('error', $clientFactoryDefinition->getArgument('$logLevel'));
    }

    public function testDriversAreRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new Neo4jExtension();
        $configs = [
            'drivers' => [
                ['alias' => 'main', 'dsn' => 'bolt://localhost:7687', 'profiling' => true],
                ['alias' => 'backup', 'dsn' => 'bolt://backup:7687', 'profiling' => false],
            ],
        ];

        $extension->load([$configs], $container);

        $this->assertTrue($container->hasDefinition('neo4j.driver.main'));
        $this->assertTrue($container->hasDefinition('neo4j.driver.backup'));
    }

    public function testProfilingEnabledForDebug(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new Neo4jExtension();
        $configs = [
            'drivers' => [
                ['alias' => 'default', 'dsn' => 'bolt://localhost:7687', 'profiling' => null],
            ],
        ];

        $extension->load([$configs], $container);

        $this->assertTrue($container->hasDefinition('neo4j.data_collector'));
        $this->assertTrue($container->hasDefinition('neo4j.subscriber'));

        $subscriberDefinition = $container->getDefinition('neo4j.subscriber');
        $this->assertEquals(['default'], $subscriberDefinition->getArgument(0));
    }
}
