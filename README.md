# octostan

PHPStan for October CMS

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
        - plugins/your-vendor-name

services:
    - class: OFFLINE\Octostan\Extensions\OctoberRelationsExtension
      tags:
          - phpstan.broker.propertiesClassReflectionExtension


```
