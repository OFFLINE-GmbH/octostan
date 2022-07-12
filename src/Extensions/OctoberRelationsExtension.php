<?php

namespace OFFLINE\Octostan\Extensions;

use NunoMaduro\Larastan\Properties\ModelProperty;
use October\Rain\Database\Model;
use OFFLINE\Octostan\Definitions\RelationDefinition;
use OFFLINE\Octostan\Helpers\RelationHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;

/**
 * Handle October's relation properties on Model classes.
 */
class OctoberRelationsExtension implements \PHPStan\Reflection\PropertiesClassReflectionExtension
{
    /**
     * Check if a property exists on a Model.
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (!$classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        try {
            RelationHelper::findRelation($classReflection, $propertyName);
        } catch (MissingPropertyFromReflectionException $e) {
            return false;
        }

        return true;
    }

    public function getProperty(
        ClassReflection $classReflection,
        string $propertyName
    ): PropertyReflection {
        $relation = RelationHelper::findRelation($classReflection, $propertyName);

        $relatedType = $this->getType($relation, $relation->modelClass);

        return new ModelProperty($classReflection, $relatedType, new NeverType(), false);
    }

    /**
     * Build the related model type.
     */
    protected function getType(RelationDefinition $relation, string $relatedModelClass)
    {
        $relatedModelType = new ObjectType($relatedModelClass);

        // Don't bother to find out what morph relations return.
        if ($relation->isMixedType()) {
            return new \PHPStan\Type\MixedType();
        }

        // For Collection types, return a Collection<int, model>
        if ($relation->isCollectionType()) {
            $collectionType = $relation->isNestedTree()
                ? \October\Rain\Database\TreeCollection::class
                : \October\Rain\Support\Collection::class;

            return new \PHPStan\Type\Generic\GenericObjectType(
                $collectionType,
                [new \PHPStan\Type\IntegerType(), $relatedModelType],
            );
        }

        // For single-type relations, return the model type directly.
        return $relatedModelType;
    }
}
