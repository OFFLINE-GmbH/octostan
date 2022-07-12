# octostan

PHPStan for October CMS

## Installation

Install this package via composer:

```
composer require offline/octoston
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
