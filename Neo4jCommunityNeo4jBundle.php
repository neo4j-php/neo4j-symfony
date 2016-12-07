<?php

namespace Neo4jCommunity\Neo4jBundle;

use Neo4jCommunity\Neo4jBundle\DependencyInjection\CommunityNeo4jExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jCommunityNeo4jBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CommunityNeo4jExtension();
    }
}
