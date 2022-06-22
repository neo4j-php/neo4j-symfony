<?php

namespace Neo4j\Neo4jBundle\Tests\Unit\Collector\Twig;

use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node;
use Neo4j\Neo4jBundle\Collector\Twig\Neo4jResultExtension;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jResultExtensionTest extends TestCase
{
    public function testEmptyArray()
    {
        $o = new Neo4jResultExtension();
        $result = $o->getType([]);

        $this->assertEquals('Empty array', $result);
    }

    public function testObject()
    {
        $o = new Neo4jResultExtension();
        $result = $o->getType($o);

        $this->assertEquals(Neo4jResultExtension::class, $result);
    }

    public function testScalar()
    {
        $o = new Neo4jResultExtension();
        $result = $o->getType(3);

        $this->assertEquals('int', $result);
    }

    public function testScalarArray()
    {
        $o = new Neo4jResultExtension();
        $result = $o->getType([3, 6.3]);

        $this->assertEquals('[int, float]', $result);
    }

    public function testArrayArray()
    {
        $o = new Neo4jResultExtension();
        $result = $o->getType([[]]);

        $this->assertEquals('[array]', $result);
    }

    public function testNote()
    {
        $o = new Neo4jResultExtension();
        $result = $o->getType(new Node(1, new CypherList(['Label']), new CypherMap()));

        $this->assertEquals('1: Label', $result);
    }
}
