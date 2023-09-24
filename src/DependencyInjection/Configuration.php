<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection;

use Laudis\Neo4j\Databags\DriverConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @psalm-type SessionConfigArray = array{
 *     fetch_size?: int|null,
 *     access_mode?: 'read'|'write'|null,
 *     database?: string|null,
 * }
 * @psalm-type SslConfigArray = array{
 *     mode: 'enable'|'disable'|'from_url'|'enable_with_self_signed'|null,
 *     verify_peer?: bool|null,
 * }
 * @psalm-type DriverConfigArray = array{
 *     acquire_connection_timeout?: int|null,
 *     user_agent?: string|null,
 *     pool_size?: int|null,
 *     ssl?: SslConfigArray|null,
 * }
 * @psalm-type TransactionConfigArray = array{
 *   timeout?: int|null
 * }
 * @psalm-type DriverAuthenticationArray = array{
 *   type?: 'basic'|'kerberos'|'dsn'|'none'|'oid',
 *   username?: string|null,
 *   password?: string|null,
 *   token?: string|null
 * }
 * @psalm-type DriverRegistrationArray = array{
 *   alias: string,
 *   dsn: string,
 *   authentication?: DriverAuthenticationArray|null,
 *   priority?: int|null,
 * }
 * @psalm-type NormalisedDriverConfig = array{
 *    default_driver_config?: DriverConfigArray|null,
 *    default_session_config?: SessionConfigArray|null,
 *    default_transaction_config?: TransactionConfigArray|null,
 *    default_driver?: string|null,
 *    drivers?: list<DriverRegistrationArray>
 *  }
 *
 * @psalm-suppress PossiblyNullReference
 * @psalm-suppress PossiblyUndefinedMethod
 * @psalm-suppress UndefinedInterfaceMethod
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('neo4j');

        $treeBuilder->getRootNode()
            ->fixXmlConfig('driver')
            ->children()
                ->append($this->decorateDriverConfig())
                ->append($this->decorateSessionConfig())
                ->append($this->decorateTransactionConfig())
                ->scalarNode('default_driver')
                    ->info('The default driver to use. Default is the first configured driver.')
                ->end()
                ->arrayNode('drivers')
                    ->info(
                        'List of drivers to use. If no drivers are configured the default driver will try to open a bolt connection without authentication on localhost over port 7687'
                    )
                    ->arrayPrototype()
                    ->fixXmlConfig('driver')
                    ->children()
                        ->scalarNode('alias')
                            ->info('The alias for this driver. Default is "default".')
                            ->defaultValue('default')
                        ->end()
                        ->scalarNode('dsn')
                            ->info('The DSN for the driver. Default is "bolt://localhost:7687".')
                            ->defaultValue('bolt://localhost:7687')
                        ->end()
                        ->arrayNode('authentication')
                            ->info('The authentication for the driver')
                            ->children()
                                ->enumNode('type')
                                    ->info('The type of authentication')
                                    ->values(['basic', 'kerberos', 'dsn', 'none', 'oid'])
                                ->end()
                                ->scalarNode('username')->end()
                                ->scalarNode('password')->end()
                                ->scalarNode('token')->end()
                            ->end()
                        ->end()
                        ->scalarNode('priority')
                            ->info('The priority of this when trying to fall back on the same alias. Default is 0')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

    private function decorateSessionConfig(): ArrayNodeDefinition
    {
        return (new ArrayNodeDefinition('default_session_config'))
            ->info('The default configuration for every session')
            ->children()
                ->scalarNode('fetch_size')
                    ->info('The amount of rows that are being fetched at once in the result cursor')
                ->end()
                ->enumNode('access_mode')
                    ->values(['read', 'write', null])
                    ->info('The default access mode for every session. Default is WRITE.')
                ->end()
                ->scalarNode('database')
                    ->info('Select the standard database to use. Default is value is null, meaning the preconfigured database by the server is used (usually a database called neo4j).')
                ->end()
            ->end();
    }

    private function decorateDriverConfig(): ArrayNodeDefinition
    {
        return (new ArrayNodeDefinition('default_driver_config'))
            ->info('The default configuration for every driver')
            ->children()
                ->scalarNode('acquire_connection_timeout')
                    ->info(sprintf(
                        'The default timeout for acquiring a connection from the connection pool. Default is %s seconds. Note that this is different from the transaction timeout.',
                        DriverConfiguration::DEFAULT_ACQUIRE_CONNECTION_TIMEOUT
                    ))
                ->end()
                ->scalarNode('user_agent')
                    ->info('The default user agent this driver. Default is "neo4j-php-client/%client-version-numer%".')
                ->end()
                ->scalarNode('pool_size')
                    ->info(sprintf(
                        'The default maximum number of connections in the connection pool. Default is %s. Connections are lazily created and closed.',
                        DriverConfiguration::DEFAULT_POOL_SIZE
                    ))
                ->end()
                ->arrayNode('ssl')
                    ->info('The SSL configuration for this driver')
                    ->children()
                        ->enumNode('mode')
                            ->values(['enable', 'disable', 'from_url', 'enable_with_self_signed', null])
                            ->info('The SSL mode for this driver')
                        ->end()
                        ->booleanNode('verify_peer')
                            ->info('Verify the peer certificate. Default is true.')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function decorateTransactionConfig(): ArrayNodeDefinition
    {
        return (new ArrayNodeDefinition('default_transaction_config'))
            ->info('The default configuration for every transaction')
            ->children()
                ->scalarNode('timeout')
                    ->info(
                        'The default transaction timeout. If null is provided it will fall back tot he preconfigured timeout on the server'
                    )
                ->end()
            ->end();
    }
}
