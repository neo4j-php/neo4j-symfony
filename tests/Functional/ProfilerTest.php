<?php

namespace Functional;

use Neo4j\Neo4jBundle\Tests\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfilerTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testProfiler()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        if ($profile = $client->getProfile()) {
            $this->assertEquals(
                2,
                $profile->getCollector('neo4j')->getQueryCount()
            );
        }
    }
}