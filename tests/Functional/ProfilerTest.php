<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Functional;

use Neo4j\Neo4jBundle\Collector\Neo4jDataCollector;
use Neo4j\Neo4jBundle\Tests\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for the Neo4j profiler data collector.
 *
 * These tests verify that the profiler correctly collects Neo4j query data
 * including successful and failed statements, execution times, and query counts.
 *
 * @requires Neo4j server running for full test execution
 */
final class ProfilerTest extends WebTestCase
{
    private const EXPECTED_QUERY_COUNT = 2;
    private const EXPECTED_SUCCESSFUL_STATEMENTS = 1;
    private const EXPECTED_FAILED_STATEMENTS = 1;

    #[\Override]
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * Check if Neo4j is available for testing.
     * If not, skip the test with a clear message.
     *
     * @throws \Exception When Neo4j connection fails for reasons other than unavailability
     */
    private function skipIfNeo4jNotAvailable(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        try {
            $neo4jClient = $container->get('neo4j.client');
            $driver = $neo4jClient->getDriver(null);
            $session = $driver->createSession();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Cannot connect to host')
                || str_contains($e->getMessage(), 'Host name lookup failure')
                || str_contains($e->getMessage(), 'Connection refused')
                || str_contains($e->getMessage(), 'Connection timed out')) {
                $this->markTestSkipped(
                    'Neo4j server is not available for testing. '.
                    'Please start a Neo4j server to run profiler tests. '.
                    'Error: '.$e->getMessage()
                );
            }
            throw $e;
        }
    }

    /**
     * Test profiler data collection for client-level operations.
     *
     * This test verifies that the profiler correctly collects:
     * - Total query count
     * - Successful statements
     * - Failed statements
     * - Execution timing data
     */
    public function testProfilerOnClient(): void
    {
        $this->skipIfNeo4jNotAvailable();

        $client = static::createClient();
        $client->enableProfiler();

        $crawler = $client->request('GET', '/client');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $profile = $client->getProfile();
        $this->assertNotNull($profile, 'Profiler should be enabled and available');
        $this->assertNotFalse($profile, 'Profiler should be enabled and available');

        /** @var Neo4jDataCollector $collector */
        $collector = $profile->getCollector('neo4j');
        $this->assertInstanceOf(Neo4jDataCollector::class, $collector);

        $this->assertEquals(
            self::EXPECTED_QUERY_COUNT,
            $collector->getQueryCount(),
            'Expected exactly 2 queries: 1 successful, 1 failed'
        );

        $successfulStatements = $collector->getSuccessfulStatements();
        $this->assertCount(
            self::EXPECTED_SUCCESSFUL_STATEMENTS,
            $successfulStatements,
            'Expected exactly 1 successful statement'
        );

        $failedStatements = $collector->getFailedStatements();
        $this->assertCount(
            self::EXPECTED_FAILED_STATEMENTS,
            $failedStatements,
            'Expected exactly 1 failed statement'
        );

        $this->assertNotEmpty($successfulStatements, 'Successful statements should not be empty');
        $this->assertNotEmpty($failedStatements, 'Failed statements should not be empty');

        $successfulStatement = $successfulStatements[0];
        $this->assertArrayHasKey('statement', $successfulStatement);
        $this->assertArrayHasKey('parameters', $successfulStatement);
        $this->assertArrayHasKey('time', $successfulStatement);

        $failedStatement = $failedStatements[0];
        $this->assertArrayHasKey('statement', $failedStatement);
        $this->assertArrayHasKey('parameters', $failedStatement);
        $this->assertArrayHasKey('time', $failedStatement);
        $this->assertArrayHasKey('error', $failedStatement);
    }

    /**
     * Test profiler data collection for session-level operations.
     *
     * This test verifies that the profiler correctly collects data
     * when using session-level Neo4j operations.
     */
    public function testProfilerOnSession(): void
    {
        $this->skipIfNeo4jNotAvailable();

        $client = static::createClient();
        $client->enableProfiler();

        $crawler = $client->request('GET', '/session');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $profile = $client->getProfile();
        $this->assertNotNull($profile, 'Profiler should be enabled and available');
        $this->assertNotFalse($profile, 'Profiler should be enabled and available');

        /** @var Neo4jDataCollector $collector */
        $collector = $profile->getCollector('neo4j');
        $this->assertInstanceOf(Neo4jDataCollector::class, $collector);

        $this->assertEquals(
            self::EXPECTED_QUERY_COUNT,
            $collector->getQueryCount(),
            'Expected exactly 2 queries: 1 successful, 1 failed'
        );

        $successfulStatements = $collector->getSuccessfulStatements();
        $this->assertCount(
            self::EXPECTED_SUCCESSFUL_STATEMENTS,
            $successfulStatements,
            'Expected exactly 1 successful statement'
        );

        $failedStatements = $collector->getFailedStatements();
        $this->assertCount(
            self::EXPECTED_FAILED_STATEMENTS,
            $failedStatements,
            'Expected exactly 1 failed statement'
        );

        $successfulStatements = $collector->getSuccessfulStatements();
        if (!empty($successfulStatements)) {
            $this->assertArrayHasKey('time', $successfulStatements[0], 'Successful statements should contain timing information');
        }
    }

    /**
     * Test profiler data collection for transaction-level operations.
     *
     * This test verifies that the profiler correctly collects data
     * when using transaction-level Neo4j operations.
     */
    public function testProfilerOnTransaction(): void
    {
        $this->skipIfNeo4jNotAvailable();

        $client = static::createClient();
        $client->enableProfiler();

        $crawler = $client->request('GET', '/transaction');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $profile = $client->getProfile();
        $this->assertNotNull($profile, 'Profiler should be enabled and available');
        $this->assertNotFalse($profile, 'Profiler should be enabled and available');

        /** @var Neo4jDataCollector $collector */
        $collector = $profile->getCollector('neo4j');
        $this->assertInstanceOf(Neo4jDataCollector::class, $collector);

        $this->assertEquals(
            self::EXPECTED_QUERY_COUNT,
            $collector->getQueryCount(),
            'Expected exactly 2 queries: 1 successful, 1 failed'
        );

        $successfulStatements = $collector->getSuccessfulStatements();
        $this->assertCount(
            self::EXPECTED_SUCCESSFUL_STATEMENTS,
            $successfulStatements,
            'Expected exactly 1 successful statement'
        );

        $failedStatements = $collector->getFailedStatements();
        $this->assertCount(
            self::EXPECTED_FAILED_STATEMENTS,
            $failedStatements,
            'Expected exactly 1 failed statement'
        );

        $successfulStatements = $collector->getSuccessfulStatements();
        if (!empty($successfulStatements)) {
            $this->assertArrayHasKey('time', $successfulStatements[0], 'Transaction statements should contain timing information');
        }
    }

    /**
     * Test profiler data collector availability and basic functionality.
     *
     * This test verifies that the Neo4j data collector is properly registered
     * and returns expected default values when no queries have been executed.
     */
    public function testProfilerDataCollectorAvailability(): void
    {
        $client = static::createClient();
        $client->enableProfiler();

        $crawler = $client->request('GET', '/');

        $profile = $client->getProfile();
        $this->assertNotNull($profile, 'Profiler should be enabled and available');
        $this->assertNotFalse($profile, 'Profiler should be enabled and available');

        /** @var Neo4jDataCollector $collector */
        $collector = $profile->getCollector('neo4j');
        $this->assertInstanceOf(Neo4jDataCollector::class, $collector);

        $this->assertEquals(0, $collector->getQueryCount(), 'Query count should be 0 when no queries executed');
        $this->assertCount(0, $collector->getSuccessfulStatements(), 'No successful statements expected');
        $this->assertCount(0, $collector->getFailedStatements(), 'No failed statements expected');
    }

    /**
     * Test profiler data collector name and toolbar integration.
     *
     * This test verifies that the data collector is properly configured
     * for integration with the Symfony profiler toolbar.
     */
    public function testProfilerDataCollectorConfiguration(): void
    {
        $client = static::createClient();
        $client->enableProfiler();

        $crawler = $client->request('GET', '/');

        $profile = $client->getProfile();
        $this->assertNotNull($profile);
        $this->assertNotFalse($profile);

        /** @var Neo4jDataCollector $collector */
        $collector = $profile->getCollector('neo4j');
        $this->assertInstanceOf(Neo4jDataCollector::class, $collector);

        $this->assertEquals('neo4j', $collector->getName(), 'Collector name should be "neo4j"');

        $serialized = serialize($collector);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(Neo4jDataCollector::class, $unserialized);
        $this->assertEquals($collector->getQueryCount(), $unserialized->getQueryCount());
    }
}
