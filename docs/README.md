# Neo4j Symfony Bundle

[![Latest Version](https://img.shields.io/github/release/neo4j-contrib/neo4j-symfony.svg?style=flat-square)](https://github.com/neo4j-contrib/neo4j-symfony/releases)
[![Build Status](https://img.shields.io/travis/neo4j-contrib/neo4j-symfony/master.svg?style=flat-square)](https://travis-ci.org/neo4j-contrib/neo4j-symfony)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/neo4j-contrib/neo4j-symfony.svg?style=flat-square)](https://scrutinizer-ci.com/g/neo4j-contrib/neo4j-symfony)
[![Quality Score](https://img.shields.io/scrutinizer/g/neo4j-contrib/neo4j-symfony.svg?style=flat-square)](https://scrutinizer-ci.com/g/neo4j-contrib/neo4j-symfony)
[![Total Downloads](https://img.shields.io/packagist/dt/neo4j/neo4j-bundle.svg?style=flat-square)](https://packagist.org/packages/neo4j/neo4j-bundle)


Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require neo4j/neo4j-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require neo4j/neo4j-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    \Neo4j\Neo4jBundle\Neo4jBundle::class => ['all' => true],
];
```

## Documentation

The bundle is a convenient way of registering services. We register `Connections`, 
`Clients` and `EntityManagers`. You will always have alias for the default services:

 * neo4j.driver
 * neo4j.client


### Minimal configuration

```yaml
neo4j:
  drivers:
    default: ~
```

With the minimal configuration we have services named:
 * neo4j.connection.default
 * neo4j.client.default
 * neo4j.entity_manager.default*

### Full configuration example

This example configures the client to contain two instances.

```yaml
neo4j:
  profiling: true
  default_driver: high-availability
  connections:
    - alias: high-availability
      dsn: 'neo4j://core1.mydomain.com:7687'
      authentication:
        type: 'oidc'
        token: '%neo4j.openconnect-id-token%'
      priority: 1
    # Overriding the alias makes it so that there is a backup server to use in case
    # the routing table cannot be fetched through the driver with a higher priority
    # but the same alias.
    # Once the table is fetched it will use that information to auto-route as usual.
    - alias: high-availability
      dsn: 'neo4j://core2.mydomain.com:7687'
      priority: 0
      authentication:
        type: 'oidc'
        token: '%neo4j.openconnect-id-token%'
    - alias: backup-instance
      dsn: 'bolt://localhost:7687'
      authentication:
        type: basic
        username: '%neo4j.backup-user%'
        password: '%neo4j.backup-pass%'
```

## Testing

``` bash
$ composer test
```

## Example application

See an example application at https://github.com/neo4j-examples/movies-symfony-php-bolt (legacy project)

## License

The MIT License (MIT). Please see [License File](../LICENSE) for more information.
