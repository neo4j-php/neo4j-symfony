<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection;

use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Contracts\SessionInterface;
use Neo4j\Neo4jBundle\Builders\ClientBuilder;
use Neo4j\Neo4jBundle\Collector\Neo4jDataCollector;
use Neo4j\Neo4jBundle\EventHandler;
use Neo4j\Neo4jBundle\EventListener\Neo4jProfileListener;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @psalm-import-type NormalisedDriverConfig from Configuration
 */
final class Neo4jExtension extends Extension
{
    #[Override]
    public function load(array $configs, ContainerBuilder $container): ContainerBuilder
    {
        $configuration = new Configuration();
        $mergedConfig = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $defaultAlias = $mergedConfig['default_driver'] ?? $mergedConfig['drivers'][0]['alias'] ?? 'default';
        $container->setDefinition('neo4j.event_handler', new Definition(EventHandler::class))
            ->setAutowired(true)
            ->addTag('neo4j.event_handler')
            ->setArgument(1, $defaultAlias);

        $container->getDefinition('neo4j.client_factory')
            ->setArgument('$driverConfig', $mergedConfig['default_driver_config'] ?? null)
            ->setArgument('$sessionConfig', $mergedConfig['default_session_config'] ?? null)
            ->setArgument('$transactionConfig', $mergedConfig['default_transaction_config'] ?? null)
            ->setArgument('$connections', $mergedConfig['drivers'] ?? [])
            ->setArgument('$defaultDriver', $mergedConfig['default_driver'] ?? null)
            ->setArgument('$builder', new Reference(ClientBuilder::class, ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->setArgument('$logLevel', $mergedConfig['min_log_level'] ?? null)
            ->setArgument('$logger', new Reference(LoggerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->setAbstract(false);

        $container->getDefinition('neo4j.driver')
            ->setArgument(0, $defaultAlias);

        foreach ($mergedConfig['drivers'] as $driverConfig) {
            $container
                ->setDefinition(
                    'neo4j.driver.'.$driverConfig['alias'],
                    (new Definition(DriverInterface::class))
                        ->setFactory([new Reference('neo4j.client'), 'getDriver'])
                        ->setArgument(0, $driverConfig['alias'])
                )
                ->setPublic(true);

            $container
                ->setDefinition(
                    'neo4j.session.'.$driverConfig['alias'],
                    (new Definition(SessionInterface::class))
                        ->setFactory([new Reference('neo4j.driver.'.$driverConfig['alias']), 'createSession'])
                        ->setShared(false)
                )
                ->setPublic(true);
        }

        $enabledProfiles = [];
        foreach ($mergedConfig['drivers'] as $driver) {
            if (true === $driver['profiling'] || (null === $driver['profiling'] && $container->getParameter(
                'kernel.debug'
            ))) {
                $enabledProfiles[] = $driver['alias'];
            }
        }

        if (0 !== count($enabledProfiles)) {
            $container->setDefinition(
                'neo4j.data_collector',
                (new Definition(Neo4jDataCollector::class))
                    ->setAutowired(true)
                    ->addTag('data_collector', [
                        'id' => 'neo4j',
                        'priority' => 500,
                    ])
            );

            $container->setAlias(Neo4jProfileListener::class, 'neo4j.subscriber');

            $container->setDefinition(
                'neo4j.subscriber',
                (new Definition(Neo4jProfileListener::class))
                    ->setArgument(0, $enabledProfiles)
                    ->addTag('kernel.event_subscriber')
            );
        }

        return $container;
    }

    #[Override]
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    #[Override]
    public function getAlias(): string
    {
        return 'neo4j';
    }
}
