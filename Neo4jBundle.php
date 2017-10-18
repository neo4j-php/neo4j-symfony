<?php

namespace Neo4j\Neo4jBundle;

use GraphAware\Bolt\Result\Type\Node;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Metadata\Factory\Annotation\AnnotationGraphEntityMetadataFactory;
use GraphAware\Neo4j\OGM\Proxy\ProxyFactory;
use Neo4j\Neo4jBundle\DependencyInjection\Neo4jExtension;
use Neo4j\Neo4jBundle\DependencyInjection\ProxyAutoloader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jBundle extends Bundle {

	private $autoloader;

	/**
	 * Boots the Bundle.
	 * Registers class autoloder for cached/uncached proxy entity classes
	 */
	public function boot() {

		if ( $this->container->hasParameter( 'neo4j.cache_dir' ) ) {
			// See https://github.com/symfony/symfony/pull/3419 for usage of references
			$container = &$this->container;

			// generates proxy classes if autoloader cant find one in cache
			$proxyGenerator = function ( $proxyDir, $proxyPrefix, $className ) use ( &$container ) {

				$originalClassName = str_replace( '_', '\\', substr( $className, strlen( $proxyPrefix ) ) );
				/** @var $registry Registry */
				$registry = $container->get( 'neo4j.entity_managers_registry' );

				// iterates through all entity managers to auto-generate desired proxy file
				/** @var $em EntityManager */
				foreach ( $registry->getManagers() as $em ) {

					/** @var AnnotationGraphEntityMetadataFactory $metadataFactory */
					$metadataFactory = $em->getMetadataFactory();

					$classMetadata = $metadataFactory->create( $originalClassName );
					$proxyFactory  = new ProxyFactory( $em, $classMetadata );
					//@todo: line below must be replaced with $proxyFactory->createProxy(); when createProxy() method will be public
					$proxyFactory->fromNode( new Node( - 1 ) );

					clearstatcache( true, ProxyAutoloader::resolveFile( $em->getProxyDirectory(), $proxyPrefix, $originalClassName ) );
					break;
				}
			};

			$this->autoloader = ProxyAutoloader::register( $container->getParameter( 'neo4j.cache_dir' ), 'neo4j_ogm_proxy_', $proxyGenerator );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function shutdown() {


		if ( null !== $this->autoloader ) {
			spl_autoload_unregister( $this->autoloader );
			$this->autoloader = null;
		}

		// Clear all entity managers to clear references to entities for GC
		if ( $this->container->hasParameter( 'neo4j.entity_managers' ) ) {
			foreach ( $this->container->getParameter( 'neo4j.entity_managers' ) as $id ) {
				if ( $this->container->initialized( $id ) ) {
					$this->container->get( $id )->clear();
				}
			}
		}

		// Close all connections to avoid reaching too many connections in the process when booting again later (tests)
		if ( $this->container->hasParameter( 'neo4j.connections' ) ) {
			foreach ( $this->container->getParameter( 'neo4j.connections' ) as $id ) {
				if ( $this->container->initialized( $id ) ) {
					//@todo: this line will create new session if current session isn`t started, then close it.
					$this->container->get( $id )->getSession()->close();
				}
			}
		}

	}

	public function getContainerExtension() {
		return new Neo4jExtension();
	}
}
