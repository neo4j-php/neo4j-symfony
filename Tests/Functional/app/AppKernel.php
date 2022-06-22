<?php

namespace Neo4j\Neo4jBundle\Tests\Functional\app;

use Neo4j\Neo4jBundle\Neo4jBundle;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    private $config;

    public function __construct($config)
    {
        parent::__construct('test', true);

        $fs = new Filesystem();

        if (!$fs->isAbsolutePath($config)) {
            $config = __DIR__.'/config/'.$config;
        }

        if (!file_exists($config)) {
            throw new RuntimeException(sprintf('The config file "%s" does not exist', $config));
        }

        $this->config = $config;
    }

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new Neo4jBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->config);
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/Neo4jBundle';
    }

    public function serialize()
    {
        return $this->config;
    }

    public function unserialize($config)
    {
        $this->__construct($config);
    }

    protected function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PublicServicesForFunctionalTestsPass());
    }
}

class PublicServicesForFunctionalTestsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $aliases = [
            'neo4j.connection',
            'neo4j.client',
            'neo4j.entity_manager',
        ];
        foreach ($aliases as $alias) {
            if ($container->hasAlias($alias)) {
                $container->getAlias($alias)->setPublic(true);
            }
        }
    }
}
