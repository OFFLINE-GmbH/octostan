# octostan

PHPStan for October CMS

## State of this library

This is currently just a proof of concept. Getting PHPStan to fully integrate with October CMS
will require a lot of work and a lot of custom code. `octostan` is currently (July 2022) the most
optimized PHPStan configuration for October CMS but has a long way to go to become actually usuable.

Contributions are welcome!


## Work in progress

* [X] Detection of relations via October's model properties
* [X] Checks for existing relations in `->with()`
* [X] Auto-detects model properties from migration files
* [ ] Returns October Query Builder for all Eloquent query methods

## Installation

Install this package via composer:

```
composer require offline/octostan
```

Create a `phpstan.neon` file in your project. You can find an example below.

To run PHPStan, use this command:

```bash
./vendor/bin/phpstan analyze 
```

## Example phpstan.neon

```neon
includes:
    - ./vendor/offline/octostan/extension.neon

parameters:
    level: 5
    checkMissingIterableValueType: false
    excludePaths:
        - plugins/**/tests
        - plugins/**/views
        - plugins/**/partials
        - plugins/**/controllers/**/*.php
        - plugins/**/formwidgets/**/partials/*.php
        
    paths:
        - plugins/your-vendor-name # Change this!
```
