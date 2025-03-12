<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\Unit\DependencyInjection;

use Neo4j\Neo4jBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Psr\Log\LogLevel;

final class ConfigurationTest extends TestCase
{

    public function testDriverConfiguration(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, [
            'neo4j' => [
                'default_driver' => 'neo4j_driver',
                'drivers' => [
                    [
                        'alias' => 'custom_driver',
                        'dsn' => 'bolt://custom-host:7687',
                        'profiling' => true,
                        'authentication' => [
                            'type' => 'basic',
                            'username' => 'user',
                            'password' => 'password',
                        ],
                        'priority' => 10,
                    ],
                ],
            ],
        ]);

        $this->assertSame('neo4j_driver', $config['default_driver']);
        $this->assertCount(1, $config['drivers']);
        $this->assertSame('custom_driver', $config['drivers'][0]['alias']);
        $this->assertSame('bolt://custom-host:7687', $config['drivers'][0]['dsn']);
        $this->assertTrue($config['drivers'][0]['profiling']);
        $this->assertSame(10, $config['drivers'][0]['priority']);

        $this->assertSame('basic', $config['drivers'][0]['authentication']['type']);
        $this->assertSame('user', $config['drivers'][0]['authentication']['username']);
        $this->assertSame('password', $config['drivers'][0]['authentication']['password']);
    }

    public function testSessionConfiguration(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, [
            'neo4j' => [
                'default_session_config' => [
                    'fetch_size' => 500,
                    'access_mode' => 'read',
                    'database' => 'testdb',
                ],
            ],
        ]);

        $this->assertSame(500, $config['default_session_config']['fetch_size']);
        $this->assertSame('read', $config['default_session_config']['access_mode']);
        $this->assertSame('testdb', $config['default_session_config']['database']);
    }

    public function testTransactionConfiguration(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, [
            'neo4j' => [
                'default_transaction_config' => [
                    'timeout' => 300,
                ],
            ],
        ]);

        $this->assertSame(300, $config['default_transaction_config']['timeout']);
    }

    public function testSslConfiguration(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, [
            'neo4j' => [
                'default_driver_config' => [
                    'ssl' => [
                        'mode' => 'enable',
                        'verify_peer' => false,
                    ],
                ],
            ],
        ]);

        $this->assertSame('enable', $config['default_driver_config']['ssl']['mode']);
        $this->assertFalse($config['default_driver_config']['ssl']['verify_peer']);
    }
}
