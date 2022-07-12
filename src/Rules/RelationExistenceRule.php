<?php

namespace OFFLINE\Octostan\Rules;

use October\Rain\Database\Relations\Relation;
use OFFLINE\Octostan\Helpers\RelationHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use ReflectionException;
use PHPStan\Reflection\MissingPropertyFromReflectionException;

class RelationExistenceRule extends \NunoMaduro\Larastan\Rules\RelationExistenceRule
{
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof MethodCall && !$node instanceof Node\Expr\StaticCall) {
            return [];
        }

        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        if (!in_array($node->name->name, [
            'has',
            'with',
            'orHas',
            'doesntHave',
            'orDoesntHave',
            'whereHas',
            'orWhereHas',
            'whereDoesntHave',
            'orWhereDoesntHave',
        ],
            true)
        ) {
            return [];
        };

        $args = $node->getArgs();

        if (count($args) < 1) {
            return [];
        }

        $valueType = $scope->getType($args[0]->value);

        if (!$valueType instanceof ConstantStringType && !$valueType instanceof ConstantArrayType) {
            return [];
        }

        if ($valueType instanceof ConstantStringType) {
            $relations = [$valueType];
        } elseif ($valueType->getKeyType()->generalize(GeneralizePrecision::lessSpecific()) instanceof IntegerType) {
            $relations = $valueType->getValueTypes();
        } else {
            $relations = $valueType->getKeyTypes();
        }

        $calledOnNode = $node instanceof MethodCall ? $node->var : $node->class;
        if (!$calledOnNode || !$calledOnNode->class) {
            return [];
        }
        $modelName = $calledOnNode->class->toCodeString();

        $type = new ObjectType($modelName);

        $classReflection = $type->getClassReflection();
        if (!$classReflection) {
            return [];
        }

        $closure = function (ClassReflection $classReflection, Node $node, string $relationName) {
            try {
                RelationHelper::findRelation($classReflection, $relationName, $node);
            } catch (MissingPropertyFromReflectionException|ReflectionException $ex) {
                return [
                    $this->getRuleError($relationName, $classReflection, $node),
                ];
            }
            return [];
        };

        $errors = [];

        foreach ($relations as $relationType) {
            $relationName = explode(':', $relationType->getValue())[0];

            // Nested relations.
            if (str_contains($relationName, '.')) {

                $relations = explode('.', $relationName);

                foreach ($relations as $relation) {
                    $result = $closure($classReflection, $node, $relation);
                    if (count($result) > 0) {
                        return $result;
                    }

                    // Move along the relations path. Replace the $classReflection with the related model.
                    $instance = new $modelName;
                    try {
                        $relatedModel = $instance->$relation()->make();
                        $type = new ObjectType(get_class($relatedModel));
                        $classReflection = $type->getClassReflection();
                    } catch (\Exception $ex) {
                        return [
                            $this->getRuleError($relation, $classReflection, $node),
                        ];
                    }

                }

                return $errors;
            }

            $errors += $closure($classReflection, $node, $relationName);
        }

        return $errors;
    }

    private function getRuleError(
        string $relationName,
        \PHPStan\Reflection\ClassReflection $modelReflection,
        Node $node
    ): \PHPStan\Rules\RuleError {
        return RuleErrorBuilder::message(sprintf("Relation '%s' is not defined on %s model.", $relationName,
            $modelReflection->getName()))
            ->identifier('rules.relationExistence')
            ->line($node->getAttribute('startLine'))
            ->build();
    }
}
