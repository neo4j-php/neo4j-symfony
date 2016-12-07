<?php

namespace Neo4j\Neo4jBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BaseTestCase extends WebTestCase
{
    protected static function getKernelClass()
    {
        require_once __DIR__.'/app/AppKernel.php';

        return 'Neo4j\Neo4jBundle\Tests\Functional\app\AppKernel';
    }

    protected static function createKernel(array $options = [])
    {
        $class = self::getKernelClass();

        return new $class(
            isset($options['config']) ? $options['config'] : 'default.yml'
        );
    }
}
