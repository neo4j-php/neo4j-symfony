<?php

namespace Neo4j\Neo4jBundle\Tests\Functional;

use Neo4j\Neo4jBundle\Collector\Neo4jDataCollector;
use Neo4j\Neo4jBundle\Tests\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfilerTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testProfiler(): void
    {
        $client = static::createClient();
        $client->enableProfiler();

        // Calls Neo4j\Neo4jBundle\Tests\App\Controller\TestController::__invoke
        $client->request('GET', '/');

        if ($profile = $client->getProfile()) {
            /** @var Neo4jDataCollector $collector */
            $collector = $profile->getCollector('neo4j');
            $this->assertEquals(
                2,
                $collector->getQueryCount()
            );
            $successfulStatements = $collector->getSuccessfulStatements();
            $failedStatements = $collector->getFailedStatements();
            $this->assertCount(1, $successfulStatements);
            $this->assertCount(1, $failedStatements);
        }
    }
}
