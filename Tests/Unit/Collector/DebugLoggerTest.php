<?php

namespace Neo4jCommunity\Neo4jBundle\Tests\Unit\Collector;

use GraphAware\Bolt\Result\Result;
use GraphAware\Common\Cypher\Statement;
use GraphAware\Neo4j\Client\Exception\Neo4jException;
use Neo4jCommunity\Neo4jBundle\Collector\DebugLogger;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DebugLoggerTest extends \PHPUnit_Framework_TestCase
{
    public function testCounter()
    {
        $o = new DebugLogger();
        $statement = Statement::create('foo');
        $o->addStatement($statement);
        $o->addResult(new Result($statement));

        $o->addStatement($statement);
        $o->addException(new Neo4jException());

        $statements = $o->getStatements();
        $results = $o->getResults();
        $exceptions = $o->getExceptions();

        $this->assertTrue(isset($statements[1]));
        $this->assertTrue(isset($results[1]));
        $this->assertFalse(isset($exceptions[1]));

        $this->assertTrue(isset($statements[2]));
        $this->assertFalse(isset($results[2]));
        $this->assertTrue(isset($exceptions[2]));
    }
}
