<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection;

use Laudis\Neo4j\Contracts\ClientInterface;
use function sprintf;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->handleClients($config, $container);

        $container->setAlias('neo4j.client', 'neo4j.client.default')
            ->setPublic(true);
        $container->setAlias(ClientInterface::class, 'neo4j.client.default')
            ->setPublic(true);

        // Configure toolbar
        if ($this->isConfigEnabled($container, $config['profiling'])) {
            $loader->load('data-collector.xml');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration((bool) $container->getParameter('kernel.debug'));
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'neo4j';
    }

    private function handleClients(array &$config, ContainerBuilder $container): void
    {
        $firstName = '';
        foreach ($config['connections'] as $name => $data) {
            if ('' === $firstName || 'default' === $name) {
                $firstName = $name;
            }
        }

        // Make sure we got a 'default'
        if ('default' !== $firstName) {
            $config['connections']['default'] = $config['connections'][$firstName];
        }

        if (empty($config['clients'])) {
            // Add default entity manager if none set.
            $config['clients']['default'] = ['connections' => ['default']];
        }

        foreach ($config['clients'] as $name => $data) {
            $connections = [];
            $serviceId = sprintf('neo4j.client.%s', $name);

            foreach ($data['connections'] as $connectionName) {
                if (empty($config['connections'][$connectionName])) {
                    throw new InvalidConfigurationException(sprintf('Client "%s" is configured to use connection named "%s" but there is no such connection', $name, $connectionName));
                }
                $connections[] = $connectionName;
            }
            if (empty($connections)) {
                $connections[] = 'default';
            }

            $definition = new ChildDefinition('neo4j.client.abstract');

            $container->setDefinition($serviceId, $definition)
                ->setArguments([$connections, $config['connections'], null])
                ->setPublic(true);
        }
    }
}
