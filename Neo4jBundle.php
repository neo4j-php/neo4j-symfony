<?php

namespace Neo4j\Neo4jBundle;

use Neo4j\Neo4jBundle\DependencyInjection\Neo4jExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jBundle extends Bundle
{
    /** @var callable(string):void|null */
    private $autoloader;

    public function getContainerExtension(): Neo4jExtension
    {
        return new Neo4jExtension();
    }

    public function boot(): void
    {
        // Register an autoloader for proxies to avoid issues when unserializing them when the OGM is used.
        if ($this->container->has('neo4j.entity_manager')) {
            // See https://github.com/symfony/symfony/pull/3419 for usage of references
            $container = &$this->container;
            $this->autoloader = static function (string $class) use (&$container): void {
                if (str_starts_with($class, 'neo4j_ogm_proxy')) {
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

    public function shutdown(): void
    {
        if (null === $this->autoloader) {
            return;
        }
        spl_autoload_unregister($this->autoloader);
    }
}
