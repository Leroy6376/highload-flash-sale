<?php

declare(strict_types=1);

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Ternary;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<Array_> */
class ComplexSingleLineArrayRule implements Rule
{
    public function getNodeType(): string
    {
        return Array_::class;
    }

    /** @param Array_ $node @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->getStartLine() === $node->getEndLine()) {
            if (! $this->isComplex($node)) {
                return [];
            }

            return [RuleErrorBuilder::message('Complex arrays must be formatted over multiple lines.')->identifier('style.complexSingleLineArray')->build()];
        }

        if (! $this->hasMultipleItemsOnSameLine($node)) {
            return [];
        }

        return [RuleErrorBuilder::message('Each item in a multiline array must be on its own line.')->identifier('style.multipleArrayItemsOnOneLine')->build()];
    }

    private function isComplex(Array_ $array): bool
    {
        if (count($array->items) >= 4) {
            return true;
        }

        return array_any(
            $array->items,
            fn ($item) => $item->value instanceof Array_ || $item->value instanceof Ternary,
        );
    }

    private function hasMultipleItemsOnSameLine(Array_ $array): bool
    {
        $itemsByLine = [];

        foreach ($array->items as $item) {
            $line = $item->getStartLine();

            if (isset($itemsByLine[$line])) {
                return true;
            }

            $itemsByLine[$line] = true;
        }

        return false;
    }
}
