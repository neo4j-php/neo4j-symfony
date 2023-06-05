<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\DriverConfiguration;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @psalm-import-type NormalisedDriverConfig from Configuration
 */
class Neo4jExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.xml');
        $loader->load('data-collector.xml');

        $debug = $container->getParameter('kernel.debug');
        if ($debug === false) {
            $container->removeDefinition('neo4j.collector.debug_collector');
        } elseif (($mergedConfig['profiling']['enabled'] ?? null) === false) {
            $container->removeDefinition('neo4j.collector.debug_collector');
        }


        $container->getDefinition('neo4j.client_factory')
            ->setArgument(1, $mergedConfig['default_driver_config'] ?? null)
            ->setArgument(2, $mergedConfig['default_session_config'] ?? null)
            ->setArgument(3, $mergedConfig['default_transaction_config'] ?? null)
            ->setArgument(4, $mergedConfig['drivers'] ?? [])
            ->setAbstract(false)
        ;

        $container->getDefinition('neo4j.driver')
            ->setArgument(0, $mergedConfig['drivers']['alias'] ?? 'default');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'neo4j';
    }
}
