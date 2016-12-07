<?php

namespace Neo4j\Bundle;

use Neo4j\Bundle\DependencyInjection\Neo4jExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new Neo4jExtension();
    }
}
