<?php

declare(strict_types=1);

namespace App\PHPStan\Rules;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/** @implements Rule<Expr> */
final class NoDirectPersistenceInAdaptersRule implements Rule
{
    /** @var list<string> */
    private const array DATABASE_WRITE_METHODS = [
        'create',
        'createMany',
        'delete',
        'deleteQuietly',
        'firstOrCreate',
        'forceDelete',
        'insert',
        'restore',
        'save',
        'saveMany',
        'update',
        'updateOrCreate',
        'upsert',
    ];

    /** @var list<string> */
    private const array FILE_WRITE_METHODS = [
        'append',
        'copy',
        'delete',
        'deleteDirectory',
        'move',
        'prepend',
        'put',
        'putFile',
        'putFileAs',
        'store',
        'storeAs',
    ];

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (preg_match('~/app/(Http|Filament)/~', str_replace('\\', '/', $scope->getFile())) !== 1) {
            return [];
        }

        if ($node instanceof MethodCall) {
            return $this->processMethodCall($node, $scope);
        }

        if ($node instanceof StaticCall) {
            return $this->processStaticCall($node, $scope);
        }

        return [];
    }

    /** @return list<IdentifierRuleError> */
    private function processMethodCall(MethodCall $node, Scope $scope): array
    {
        if (! $node->name instanceof Identifier) {
            return [];
        }

        $method = $node->name->toString();
        $calledOnType = $scope->getType($node->var);

        if (in_array($method, self::DATABASE_WRITE_METHODS, true) && $this->isDatabaseType($calledOnType)) {
            return [$this->error($method, 'database')];
        }

        if (in_array($method, self::FILE_WRITE_METHODS, true) && $this->isFileType($calledOnType)) {
            return [$this->error($method, 'filesystem')];
        }

        return [];
    }

    /** @return list<IdentifierRuleError> */
    private function processStaticCall(StaticCall $node, Scope $scope): array
    {
        if (! $node->class instanceof Name || ! $node->name instanceof Identifier) {
            return [];
        }

        $method = $node->name->toString();

        if (! in_array($method, self::DATABASE_WRITE_METHODS, true)) {
            return [];
        }

        $className = $scope->resolveName($node->class);

        if (! (new ObjectType(Model::class))->isSuperTypeOf(new ObjectType($className))->yes()) {
            return [];
        }

        return [$this->error($method, 'database')];
    }

    private function isDatabaseType(Type $type): bool
    {
        return (new ObjectType(Model::class))->isSuperTypeOf($type)->yes()
            || (new ObjectType(Builder::class))->isSuperTypeOf($type)->yes()
            || (new ObjectType(Relation::class))->isSuperTypeOf($type)->yes();
    }

    private function isFileType(Type $type): bool
    {
        return (new ObjectType(FilesystemAdapter::class))->isSuperTypeOf($type)->yes()
            || (new ObjectType(UploadedFile::class))->isSuperTypeOf($type)->yes();
    }

    private function error(string $method, string $target): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            "Adapters must delegate {$target} writes to an Action; direct {$method}() calls are forbidden.",
        )->identifier('architecture.directPersistenceInAdapter')->build();
    }
}
