<?php

namespace OFFLINE\Octostan\Rules;

use October\Rain\Database\Relations\Relation;
use OFFLINE\Octostan\Helpers\RelationHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

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

        // TODO: Implement relation checks here

        return [];
    }

    private function getRuleError(
        string $relationName,
        \PHPStan\Reflection\ClassReflection $modelReflection,
        Node $node
    ): \PHPStan\Rules\RuleError {
        return RuleErrorBuilder::message(sprintf("Relation '%s' is not found in %s model.", $relationName,
            $modelReflection->getName()))
            ->identifier('rules.relationExistence')
            ->line($node->getAttribute('startLine'))
            ->build();
    }
}
