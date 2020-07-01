<?php

namespace Neo4j\Neo4jBundle\Tests\Unit\DependencyInjection;

use Neo4j\Neo4jBundle\DependencyInjection\Neo4jExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jExtensionTest extends AbstractExtensionTestCase
{
    protected function getMinimalConfiguration(): array
    {
        $this->setParameter('kernel.cache_dir', 'foo');

        return ['connections' => ['default' => ['port' => 7474]]];
    }

    public function testDataCollectorLoaded()
    {
        $this->setParameter('kernel.debug', true);
        $this->load();

        $this->assertContainerBuilderHasService('neo4j.collector.debug_collector', 'Neo4j\Neo4jBundle\Collector\Neo4jDataCollector');
    }

    public function testDataCollectorNotLoadedInNonDebug()
    {
        $this->setParameter('kernel.debug', false);
        $this->load();

        $this->assertContainerBuilderNotHasService('neo4j.collector.debug_collector');
    }

    public function testDataCollectorNotLoadedWhenDisabled()
    {
        $this->setParameter('kernel.debug', true);
        $this->load(['profiling' => ['enabled' => false]]);

        $this->assertContainerBuilderNotHasService('neo4j.collector.debug_collector');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new Neo4jExtension(),
        ];
    }

    public function testDefaultDsn()
    {
        $this->setParameter('kernel.debug', false);
        $this->load();
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('neo4j.connection.default', 1, 'bolt://neo4j:neo4j@localhost:7474');
    }

    public function testDsn()
    {
        $this->setParameter('kernel.debug', false);
        $config = ['connections' => [
            'default' => [
                'dsn' => 'bolt://foo:bar@localhost:7687',
            ],
        ]];

        $this->load($config);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('neo4j.connection.default', 1, 'bolt://foo:bar@localhost:7687');
    }
}
