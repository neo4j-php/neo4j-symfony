<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit\Collector;
use Neo4j\Neo4jBundle\EventListener\Neo4jProfileListener;
use Neo4j\Neo4jBundle\Collector\Neo4jDataCollector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Neo4jDataCollectorTest extends TestCase
{
    private MockObject&Neo4jProfileListener $subscriber;
    private Neo4jDataCollector $collector;

    protected function setUp(): void
    {
        $this->subscriber = $this->createMock(Neo4jProfileListener::class);
        $this->collector = new Neo4jDataCollector($this->subscriber);
    }

    public function testGetName(): void
    {
        $this->assertSame('neo4j', $this->collector->getName());
    }

    public function testGetQueryCount(): void
    {
        $this->subscriber
            ->expects($this->once())
            ->method('getProfiledSummaries')
            ->willReturn([
                ['start_time' => 1000, 'query' => 'MATCH (n) RETURN n'],
            ]);

        $this->collector->collect(new Request(), new Response());

        $this->assertSame(1, $this->collector->getQueryCount());
    }

    public function testRecursiveToArray(): void
    {
        $obj = new class {
            public function toArray(): array
            {
                return ['key' => 'value'];
            }
        };
        $reflection = new \ReflectionClass($this->collector);
        $method = $reflection->getMethod('recursiveToArray');

        $result = $method->invoke($this->collector, $obj);
        $this->assertSame(['key' => 'value'], $result);
    }
}
