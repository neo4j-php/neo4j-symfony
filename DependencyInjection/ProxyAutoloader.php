<?php

declare( strict_types=1 );

namespace Neo4j\Neo4jBundle\DependencyInjection;

/**
 * Special Autoloader for Proxy classes, which are not PSR-0 compliant.
 *
 * @author Dmitrii Shargorodskii <1337.um@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de> - author of Doctrine (proxy) Autoloader
 */
class ProxyAutoloader {
    /**
     * Registers and returns autoloader callback for the given proxy dir and namespace.
     *
     * @param string $proxyDir
     * @param string $proxyNamespace
     * @param callable|null $notFoundCallback Invoked when the proxy file is not found.
     *
     * @return \Closure
     */
    public static function register( $proxyDir, $proxyNamespace, $notFoundCallback = null ) {
        $proxyNamespace = ltrim( $proxyNamespace, '\\' );

        $autoloader = function ( $className ) use ( $proxyDir, $proxyNamespace, $notFoundCallback ) {
            if ( 0 === strpos( $className, $proxyNamespace ) ) {
                $file = ProxyAutoloader::resolveFile( $proxyDir, $proxyNamespace, $className );

                if ( $notFoundCallback && ! file_exists( $file ) ) {
                    call_user_func( $notFoundCallback, $proxyDir, $proxyNamespace, $className );
                }

                require $file;
            }
        };

        spl_autoload_register( $autoloader );

        return $autoloader;
    }

    /**
     * Resolves proxy class name to a filename based on the following pattern.
     *
     * 1. if className has prefix already - do not add another prefix
     * 2. Remove namespace separators from remaining class name.
     * 3. Return PHP filename from proxy-dir with the result from 2.
     *
     * @param string $proxyDir
     * @param string $proxyPrefix
     * @param string $className
     *
     * @return string
     *
     */
    public static function resolveFile( $proxyDir, $proxyPrefix, $className ) {
        $proxyPrefix = ( strpos( $className, $proxyPrefix ) === 0 ) ? '' : $proxyPrefix;
        $fileName    = $proxyPrefix . str_replace( '\\', '_', $className );

        return $proxyDir . DIRECTORY_SEPARATOR . $fileName . '.php';
    }
}
