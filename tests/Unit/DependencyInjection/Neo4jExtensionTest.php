<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Neo4j\Neo4jBundle\Collector\Neo4jDataCollector;
use Neo4j\Neo4jBundle\DependencyInjection\Neo4jExtension;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jExtensionTest extends AbstractExtensionTestCase
{
    protected function getMinimalConfiguration(): array
    {
        $this->setParameter('kernel.cache_dir', 'foo');

        return ['drivers' => ['default' => ['dsn' => 'bolt://localhost']]];
    }

    public function testDataCollectorLoaded(): void
    {
        $this->setParameter('kernel.debug', true);
        $this->load();

        $this->assertContainerBuilderHasService('neo4j.data_collector', Neo4jDataCollector::class);
    }

    public function testDataCollectorNotLoadedInNonDebug(): void
    {
        $this->setParameter('kernel.debug', false);
        $this->load();

        $this->assertContainerBuilderNotHasService('neo4j.data_collector');
    }

    public function testDataCollectorNotLoadedWhenDisabled(): void
    {
        $this->setParameter('kernel.debug', true);
        $this->load(['drivers' => [
            'default' => [
                'profiling' => false,
            ],
        ]]);

        $this->assertContainerBuilderNotHasService('neo4j.neo4j_data_collector');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new Neo4jExtension(),
        ];
    }
}
