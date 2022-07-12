<?php

namespace OFFLINE\Octostan\Definitions;

use PHPStan\Reflection\ClassReflection;

class RelationDefinition
{
    public string $relationType;
    public string $relationName;
    public string $modelClass;
    public ClassReflection $classReflection;

    public function __construct(
        ClassReflection $classReflection,
        string $relationType,
        string $relationName,
        array|string $definition
    ) {
        $this->classReflection = $classReflection;
        $this->relationType = $relationType;
        $this->relationName = $relationName;

        if (is_array($definition)) {
            $modelClass = $definition[0];
        } else {
            $modelClass = $definition;
        }

        $this->modelClass = $modelClass;
    }

    /**
     * Check if the referenced model implements the NestedTree trait.
     */
    public function isNestedTree()
    {
        $reflection = \PHPStan\BetterReflection\Reflection\ReflectionClass::createFromInstance(new $this->modelClass);
        foreach ($reflection->getTraitNames() as $trait) {
            if (\October\Rain\Support\Str::contains($trait, 'NestedTree')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this relation returns a Collection.
     */
    public function isCollectionType()
    {
        return \October\Rain\Support\Str::endsWith($this->relationType, 'Many');
    }

    /**
     * Check if this relation is a morphed relation.
     */
    public function isMixedType()
    {
        return \October\Rain\Support\Str::contains($this->relationType, 'morph');
    }
}
