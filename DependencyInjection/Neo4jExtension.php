<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection;

use GraphAware\Neo4j\Client\Connection\Connection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->handleConnections($config, $container);
        $clientServiceIds = $this->handleClients($config, $container);
        $this->handleEntityMangers($config, $container, $clientServiceIds);

        // add aliases for the default services
        $container->setAlias('neo4j.connection', 'neo4j.connection.default');
        $container->setAlias('neo4j.client', 'neo4j.client.default');
        $container->setAlias('neo4j.entity_manager', 'neo4j.entity_manager.default');

        // Configure toolbar
        if ($this->isConfigEnabled($container, $config['profiling'])) {
            $loader->load('data-collector.xml');
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @return array with service ids
     */
    private function handleClients(array &$config, ContainerBuilder $container): array
    {
        if (empty($config['clients'])) {
            // Add default entity manager if none set.
            $config['clients']['default'] = ['connections' => ['default']];
        }

        $serviceIds = [];
        foreach ($config['clients'] as $name => $data) {
            $serviceIds[$name] = $serviceId = sprintf('neo4j.client.%s', $name);
            foreach ($data['connections'] as $connectionName) {
                if (empty($config['connections'][$connectionName])) {
                    throw new InvalidConfigurationException(sprintf(
                        'Client "%s" is configured to use connection named "%s" but there is no such connection',
                        $name,
                        $connectionName
                    ));
                }
                $connections[] = $connectionName;
            }
            if (empty($connections)) {
                $connections[] = 'default';
            }

            $container
                ->setDefinition($serviceId, new DefinitionDecorator('neo4j.client.abstract'))
                ->setArguments([$connections]);
        }

        return $serviceIds;
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     * @param array            $clientServiceIds
     *
     * @return array
     */
    private function handleEntityMangers(array &$config, ContainerBuilder $container, array $clientServiceIds): array
    {
        if (empty($config['entity_managers'])) {
            // Add default entity manager if none set.
            $config['entity_managers']['default'] = ['client' => 'default'];
        }

        $serviceIds = [];
        foreach ($config['entity_managers'] as $name => $data) {
            $serviceIds[] = $serviceId = sprintf('neo4j.entity_manager.%s', $name);
            $clientName = $data['client'];
            if (empty($clientServiceIds[$clientName])) {
                throw new InvalidConfigurationException(sprintf(
                    'EntityManager "%s" is configured to use client named "%s" but there is no such client',
                    $name,
                    $clientName
                ));
            }
            $container
                ->setDefinition($serviceId, new DefinitionDecorator('neo4j.entity_manager.abstract'))
                ->setArguments([
                    $container->getDefinition($clientServiceIds[$clientName]),
                    empty($data['cache_dir']) ? $container->getParameter('kernel.cache_dir').'/neo4j' : $data['cache_dir'],
                ]);
        }

        return $serviceIds;
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @return array with service ids
     */
    private function handleConnections(array &$config, ContainerBuilder $container): array
    {
        $serviceIds = [];
        $firstName = null;
        foreach ($config['connections'] as $name => $data) {
            if ($firstName === null || $name === 'default') {
                $firstName = $name;
            }
            $def = new Definition(Connection::class);
            $def->addArgument($name);
            $def->addArgument($this->getUrl($data));
            $serviceIds[$name] = $serviceId = 'neo4j.connection.'.$name;
            $container->setDefinition($serviceId, $def);
        }

        // Make sure we got a 'default'
        if ($firstName !== 'default') {
            $config['connections']['default'] = $config['connections'][$firstName];
        }

        // Add connections to connection manager
        $connectionManager = $container->getDefinition('neo4j.connection_manager');
        foreach ($serviceIds as $name => $serviceId) {
            $connectionManager->addMethodCall('registerExistingConnection', [$name, new Reference($serviceId)]);
        }
        $connectionManager->addMethodCall('setMaster', [$firstName]);

        return $serviceIds;
    }

    /**
     * Get URL form config.
     *
     * @param array $config
     *
     * @return string
     */
    private function getUrl(array $config): string
    {
        return sprintf(
            '%s://%s:%s@%s:%d',
            $config['schema'],
            $config['username'],
            $config['password'],
            $config['host'],
            $config['port']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    public function getAlias(): string
    {
        return 'neo4j';
    }
}
