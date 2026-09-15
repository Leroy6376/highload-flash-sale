<?php

declare(strict_types=1);

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<Stmt> */
class SingleLineArrayShapePhpDocRule implements Rule
{
    public function getNodeType(): string
    {
        return Stmt::class;
    }

    /** @param Stmt $node @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $docComment = $node->getDocComment();

        if ($docComment === null || ! str_contains($docComment->getText(), 'array{') || str_contains($docComment->getText(), "\n")) {
            return [];
        }

        return [RuleErrorBuilder::message('PHPDoc array shapes must be formatted over multiple lines.')->identifier('style.singleLineArrayShapePhpDoc')->build()];
    }
}
