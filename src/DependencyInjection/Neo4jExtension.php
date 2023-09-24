<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @psalm-import-type NormalisedDriverConfig from Configuration
 */
class Neo4jExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.xml');

        $container->getDefinition('neo4j.client_factory')
            ->setArgument(1, $mergedConfig['default_driver_config'] ?? null)
            ->setArgument(2, $mergedConfig['default_session_config'] ?? null)
            ->setArgument(3, $mergedConfig['default_transaction_config'] ?? null)
            ->setArgument(4, $mergedConfig['drivers'] ?? [])
            ->setArgument(5, new Reference(ClientInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->setArgument(6, new Reference(StreamFactoryInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->setArgument(7, new Reference(RequestFactoryInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->setAbstract(false)
        ;

        $container->getDefinition('neo4j.driver')
            ->setArgument(0, $mergedConfig['drivers']['alias'] ?? 'default');
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    public function getAlias(): string
    {
        return 'neo4j';
    }
}
