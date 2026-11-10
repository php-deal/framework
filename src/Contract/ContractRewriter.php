<?php

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014-2026, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace PhpDeal\Contract;

use Go\Instrument\Transformer\PrefilteredNodeRewriter;
use Go\Instrument\Transformer\StreamMetaData;
use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Compiles contract expressions into closures while Go! AOP weaves a file (PHP 8.5+)
 *
 * The closure is added to the attribute as the `check` argument, on the line of the expression:
 *
 *     #[Verify('$amount > 0', check: static function ($__this, $amount, ...$__arguments) { return ($amount > 0); })]
 *
 * The woven file is cached by opcache, contracts run without `eval()`, and breakpoints and stack traces point to the
 * line of the contract. Go! AOP keeps the rewritten declarations in the `...OriginalTrait` of a woven class, where
 * {@see ContractFetcher} reads them.
 *
 * @internal Registered by {@see \PhpDeal\ContractApplication::registerAspects()}
 */
final class ContractRewriter implements PrefilteredNodeRewriter
{
    /**
     * Contract attributes and whether their closure gets `$__result` and `$__old`
     */
    private const array CONTRACTS = [
        Verify::class    => false,
        Ensure::class    => true,
        Invariant::class => true,
    ];

    public array $nodeTypes {
        get => [Attribute::class];
    }

    /**
     * A contract attribute is always written with the namespace of PhpDeal: in an import, in the name or in the
     * namespace of the file
     */
    public array $sourceMarkers {
        get => ['PhpDeal'];
    }

    public function __construct(
        private readonly ContractCompiler $compiler = new ContractCompiler(),
    ) {}

    public function rewriteNode(Node $node, StreamMetaData $file, array $ancestors): bool
    {
        if (!$node instanceof Attribute || \count($ancestors) < 2) {
            return false;
        }
        $type = self::resolveName($node->name);
        if ($type === null || !isset(self::CONTRACTS[$type]) || \count($node->args) !== 1) {
            return false;
        }
        $argument = $node->args[0];
        if ($argument->unpack || !$argument->value instanceof String_
            || ($argument->name !== null && $argument->name->toString() !== 'expression')
        ) {
            return false;
        }

        $target = $ancestors[\count($ancestors) - 2];
        if ($type === Invariant::class && $target instanceof ClassLike) {
            $closure = $this->compiler->compile($argument->value->value, null, false, true);
        } elseif ($type !== Invariant::class && $target instanceof ClassMethod) {
            $parameters = self::getParameterNames($target);
            $closure    = $parameters !== null
                ? $this->compiler->compile($argument->value->value, $parameters, $target->isStatic(), self::CONTRACTS[$type])
                : null;
        } else {
            return false;
        }

        $endPosition = $argument->value->getEndTokenPos();
        if ($closure === null || !isset($file->tokenStream[$endPosition])) {
            return false;
        }
        $file->tokenStream[$endPosition]->text .= ', check: ' . $closure;

        return true;
    }

    private static function resolveName(Name $name): ?string
    {
        $resolved = $name->getAttribute('resolvedName');
        if ($resolved instanceof Name) {
            $name = $resolved;
        } elseif (!$name instanceof Name\FullyQualified) {
            return null;
        }

        return $name->toString();
    }

    /**
     * @return list<string>|null
     */
    private static function getParameterNames(ClassMethod $method): ?array
    {
        $names = [];
        foreach ($method->params as $param) {
            if (!$param->var instanceof Variable || !\is_string($param->var->name)) {
                return null;
            }
            $names[] = $param->var->name;
        }

        return $names;
    }
}
