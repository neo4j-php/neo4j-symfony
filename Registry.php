<?php

/*
 * This file is part of the Doctrine Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Neo4j\Neo4jBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * References all OGM connections and entity managers in a given Container.
 * Used mostly to get all entity managers in bundle`s ->boot() method
 *
 * @author Dmitrii Shargorodskii <1337.um@gmail.com>
 */
class Registry {
    private $container;

    /**
     * @var array
     */
    private $connections;

    /**
     * @var array
     */
    private $managers;

    /**
     * @var string
     */
    private $defaultConnection;

    /**
     * @var string
     */
    private $defaultManager;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param array $connections
     * @param array $entityManagers
     * @param $defaultConnection
     * @param $defaultManager
     */
    public function __construct( ContainerInterface $container, $connections, $entityManagers, $defaultConnection, $defaultManager ) {
        $this->container         = &$container;
        $this->connections       = $connections;
        $this->managers          = $entityManagers;
        $this->defaultConnection = $defaultConnection;
        $this->defaultManager    = $defaultManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection( $name = null ) {
        if ( null === $name ) {
            $name = $this->defaultConnection;
        }

        if ( ! isset( $this->connections[ $name ] ) ) {
            throw new \InvalidArgumentException( sprintf( 'Connection named "%s" does not exist.', $name ) );
        }

        return $this->getService( $this->connections[ $name ] );
    }

    /**
     * Fetches/creates the given services.
     *
     * A service in this context is connection or a manager instance.
     *
     * @param string $name The name of the service.
     *
     * @return object The instance of the given service.
     */
    protected function getService( $name ) {
        return $this->container->get( $name );
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionNames() {
        return $this->connections;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnections() {
        $connections = [];
        foreach ( $this->connections as $name => $id ) {
            $connections[ $name ] = $this->getService( $id );
        }

        return $connections;
    }

    /**
     * @param array $connections
     *
     * @return Registry
     */
    public function setConnections( array $connections ): Registry {
        $this->connections = $connections;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConnectionName() {
        return $this->defaultConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultManagerName() {
        return $this->defaultManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerNames() {
        return $this->managers;
    }

    /**
     * {@inheritdoc}
     */
    public function getManagers() {
        $dms = [];
        foreach ( $this->managers as $name => $id ) {
            $dms[ $name ] = $this->getService( $id );
        }

        return $dms;
    }

    /**
     * @param array $managers
     *
     * @return Registry
     */
    public function setManagers( array $managers ): Registry {
        $this->managers = $managers;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository( $persistentObjectName, $persistentManagerName = null ) {
        return $this->getManager( $persistentManagerName )->getRepository( $persistentObjectName );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function getManager( $name = null ) {
        if ( null === $name ) {
            $name = $this->defaultManager;
        }

        if ( ! isset( $this->managers[ $name ] ) ) {
            throw new \InvalidArgumentException( sprintf( 'Manager named "%s" does not exist.', $name ) );
        }

        return $this->getService( $this->managers[ $name ] );
    }

    /**
     * {@inheritdoc}
     */
    public function resetManager( $name = null ) {
        if ( null === $name ) {
            $name = $this->defaultManager;
        }

        if ( ! isset( $this->managers[ $name ] ) ) {
            throw new \InvalidArgumentException( sprintf( 'Manager named "%s" does not exist.', $name ) );
        }

        // force the creation of a new document manager
        // if the current one is closed
        $this->resetService( $this->managers[ $name ] );

        return $this->getManager( $name );
    }

    /**
     * Resets the given services.
     *
     * A service in this context is connection or a manager instance.
     *
     * @param string $name The name of the service.
     *
     * @return void
     */
    protected function resetService( $name ) {
        //@todo: implement service reset
    }

    /**
     * @param $connection
     *
     * @return Registry
     */
    public function addConnection( $connection ): Registry {
        $this->connections[] = $connection;

        return $this;
    }

    /**
     * @param string $manager
     *
     * @return Registry
     */
    public function addManager( string $manager ): Registry {
        $this->managers = $manager;

        return $this;
    }
}
