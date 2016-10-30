<?php

namespace GraphAware\Neo4jBundle\Tests\Unit\Collector\Twig;

use GraphAware\Neo4j\Client\Formatter\Type\Node;
use GraphAware\Neo4jBundle\Collector\Twig\Neo4jResultExtension;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jResultExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyArray()
    {
        $o = new Neo4jResultExtension();
        $result = $o->getType(array());

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

        $this->assertEquals('integer', $result);
    }

    public function testScalarArray()
    {
        $o = new Neo4jResultExtension();
        $result = $o->getType([3, 6.3]);

        $this->assertEquals('[integer, double]', $result);
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
        $result = $o->getType(new Node('abc', ['Label'], []));

        $this->assertEquals('abc: Label', $result);
    }
}
