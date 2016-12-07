<?php

namespace Neo4j\Bundle\Tests\Unit\DependencyInjection;

use Neo4j\Bundle\DependencyInjection\Neo4jExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jExtensionTest extends AbstractExtensionTestCase
{
    protected function getMinimalConfiguration()
    {
        $this->setParameter('kernel.cache_dir', 'foo');

        return ['connections' => ['default' => ['port' => 7474]]];
    }

    public function testDataCollectorLoaded()
    {
        $this->setParameter('kernel.debug', true);
        $this->load();

        $this->assertContainerBuilderHasService('neo4j.collector.debug_collector', 'Neo4j\Bundle\Collector\Neo4jDataCollector');
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

    protected function getContainerExtensions()
    {
        return [
            new Neo4jExtension(),
        ];
    }
}
