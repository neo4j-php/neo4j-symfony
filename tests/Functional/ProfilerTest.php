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
        // Use the same hostname that the Symfony application uses
        // In Docker environments, this is typically 'neo4j'
        $host = $_ENV['NEO4J_HOST'] ?? 'neo4j';
        $port = $_ENV['NEO4J_PORT'] ?? '7687';

        // Create a simple TCP connection test
        $socket = @fsockopen($host, (int) $port, $errno, $errstr, 5);
        if (!$socket) {
            $this->markTestSkipped(
                'Neo4j server is not available for testing. '.
                'Please start a Neo4j server to run profiler tests. '.
                "Error: Cannot connect to $host:$port - $errstr ($errno)"
            );
        }
        fclose($socket);

        // Additional check: Try to make a simple HTTP request to Neo4j's web interface
        $httpPort = $_ENV['NEO4J_HTTP_PORT'] ?? '7474';
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET',
            ],
        ]);

        $httpResponse = @file_get_contents("http://$host:$httpPort", false, $context);
        if (false === $httpResponse) {
            $this->markTestSkipped(
                'Neo4j server is not fully available for testing. '.
                'Please start a Neo4j server to run profiler tests. '.
                "Error: Cannot connect to Neo4j HTTP interface at $host:$httpPort"
            );
        }

        // Final check: Try to create a minimal Neo4j client connection
        try {
            $user = $_ENV['NEO4J_USER'] ?? 'neo4j';
            $password = $_ENV['NEO4J_PASSWORD'] ?? 'testtest';

            $client = \Laudis\Neo4j\ClientBuilder::create()
                ->withDriver('default', "bolt://$user:$password@$host:$port")
                ->build();

            // Try a simple query to verify the connection works
            $result = $client->run('RETURN 1 as test');
        } catch (\Exception $e) {
            $this->markTestSkipped(
                'Neo4j server is not properly configured for testing. '.
                'Please start a Neo4j server to run profiler tests. '.
                'Error: '.$e->getMessage()
            );
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
