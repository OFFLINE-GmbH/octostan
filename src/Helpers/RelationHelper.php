<?php

namespace OFFLINE\Octostan\Helpers;

use OFFLINE\Octostan\Definitions\RelationDefinition;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use ReflectionException;

class RelationHelper
{
    /**
     * All available relations.
     * @var array|string[]
     */
    protected array $relationTypes = [
        'belongsTo',
        'hasOne',
        'hasMany',
        'belongsToMany',
        'attachMany',
        'attachOne',
        'hasManyThrough',
        'morphTo',
        'morphManyTo',
    ];

    /**
     * Extract the relation definition from the model property.
     */
    public static function findRelation(ClassReflection $classReflection, string $propertyName): RelationDefinition
    {
        $instance = new static;

        foreach ($instance->relationTypes as $relation) {
            try {
                $property = $classReflection->getNativeProperty($relation);
                $instance = new ("\\" . $classReflection->getName());
                $value = $property->getNativeReflection()->getValue($instance);

                if (array_key_exists($propertyName, $value)) {
                    return new RelationDefinition(
                        $classReflection,
                        $relation,
                        $propertyName,
                        $value[$propertyName]
                    );
                }
            } catch (MissingPropertyFromReflectionException|ReflectionException $e) {
                // Ignore.
                continue;
            }
        }

        throw new MissingPropertyFromReflectionException(
            $classReflection->getName(),
            $propertyName
        );
    }
}