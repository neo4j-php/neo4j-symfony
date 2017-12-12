<?php

namespace Neo4j\Neo4jBundle;

use Neo4j\Neo4jBundle\DependencyInjection\Neo4jExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jBundle extends Bundle
{
    private $autoloader;

    public function getContainerExtension()
    {
        return new Neo4jExtension();
    }

    public function boot()
    {
        // Register an autoloader for proxies to avoid issues when unserializing them when the OGM is used.
        if ($this->container->has('neo4j.entity_manager')) {
            // See https://github.com/symfony/symfony/pull/3419 for usage of references
            $container = &$this->container;
            $this->autoloader = function ($class) use (&$container) {
                if (0 === strpos($class, 'neo4j_ogm_proxy')) {
                    $cacheDir = $container->getParameter('kernel.cache_dir').DIRECTORY_SEPARATOR.'neo4j';
                    $file = $cacheDir.DIRECTORY_SEPARATOR.$class.'.php';
                    if (file_exists($file)) {
                        require_once $file;
                    }
                }
            };
            spl_autoload_register($this->autoloader);
        }
    }

    public function shutdown()
    {
        if (null === $this->autoloader) {
            return;
        }
        spl_autoload_unregister($this->autoloader);
    }
}
