<?php

namespace Neo4jCommunity\Neo4jBundle\Tests\Unit\DependencyInjection;

use Neo4jCommunity\Neo4jBundle\DependencyInjection\Neo4jCommunityNeo4jExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class GraphAwareNeo4jExtensionTest extends AbstractExtensionTestCase
{
    protected function getMinimalConfiguration()
    {
        return ['connections' => ['default' => ['port' => 7474]]];
    }

    public function testDataCollectorLoaded()
    {
        $this->setParameter('kernel.debug', true);
        $this->load();

        $this->assertContainerBuilderHasService('neo4j.collector.debug_collector', 'Neo4jCommunity\Neo4jBundle\Collector\Neo4jDataCollector');
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
            new Neo4jCommunityNeo4jExtension(),
        ];
    }
}
