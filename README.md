# GraphAware Neo4j Bundle

[![Latest Version](https://img.shields.io/github/release/neo4j-community/neo4j-bundle.svg?style=flat-square)](https://github.com/neo4j-community/neo4j-bundle/releases)
[![Build Status](https://img.shields.io/travis/neo4j-community/neo4j-bundle.svg?style=flat-square)](https://travis-ci.org/neo4j-community/neo4j-bundle)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/neo4j-community/neo4j-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/neo4j-community/neo4j-bundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/neo4j-community/neo4j-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/neo4j-community/neo4j-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/neo4j-community/neo4j-bundle.svg?style=flat-square)](https://packagist.org/packages/neo4j-community/neo4j-bundle)


## Install

Via Composer

``` bash
$ composer require neo4j-community/neo4j-bundle
```

Enable the bundle in your kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Neo4jCommunity\Neo4jBundle\GraphAwareNeo4jBundle(),
    );
}
```

## Documentation

The bundle is a convenient way of registering services. We register `Connections`, 
`Clients` and `EntityManagers`. You will always have alias for the default services:

 * neo4j.connection
 * neo4j.client
 * neo4j.entity_manager


### Minimal configuration

```yaml
graph_aware_neo4j:
  connections:
    default: ~
```

With the minimal configuration we have services named:
 * neo4j.connection.default
 * neo4j.client.default
 * neo4j.entity_manager.default

### Full configuration

```yaml
graph_aware_neo4j:
  profiling: 
    enabled: true
  connections:
    default:
      schema: http #default
      host: localhost #default
      port: 7474 #default
      username: neo4j #default
      password: neo4j #default
    second_connection:
      username: foo
      password: bar
  clients:
    default:
      connections: [default, second_connection]
    other_client:
      connections: [second_connection]
    foobar: ~ # foobar client will have the "default" connection
  entity_managers:
    default: 
      client: other_clinet # defaults to "default"
      cache_dir: "%kernel.cache_dir%/neo4j" # defaults to system cache
```
With the configuration above we would have services named:
 * neo4j.connection.default
 * neo4j.connection.second_connection
 * neo4j.client.default
 * neo4j.client.other_client
 * neo4j.client.other_foobar
 * neo4j.entity_manager.default


## Testing

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
