# Change Log

The change log describes what is "Added", "Removed", "Changed" or "Fixed" between each release. 

## 0.4.2

### Added

- Autowire support by register interfaces as aliases for default services.

### Fixed

- Support for Symfony 4.2
- Better query logging on exceptions 

## 0.4.1

### Added

- Support for DSN
- Support for resettable data collectors

## 0.4.0

### Added

- Support for Symfony 4

### Fixed

- Updating the twig path for symfony flex
- Register an autoloader for proxies to avoid issues when unserializing

## 0.3.0

### Added

- Show exceptions in profiler

### Fixed

- Typo in configuration "schema" => "scheme".
- Bug where clients accidentally could share connections.

## 0.2.0

### Added

* Support for BOLT
* Test the bundle without OGM

### Changed

* Made the graphaware/neo4j-php-ogm optional

### Fixed

* Invalid alias whennot using the entity manager
* Make sure query logger has default values when exception occour.

## 0.1.0

First release
