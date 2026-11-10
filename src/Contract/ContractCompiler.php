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

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\Token;

/**
 * Compiles a contract expression into the source code of a static closure
 *
 * The closure takes the object as `$__this`, then the variables of the contract by name. It is written into the
 * attribute while Go! AOP weaves the class, so it is cached by opcache and needs no `eval()`. The code keeps the
 * meaning the expression has in `eval()`:
 * - `$this` becomes `$__this`, because a closure in an attribute must be static;
 * - names are fully qualified, because the expression is written for the global namespace without imports;
 * - comments are removed and newlines become spaces, so the woven file keeps the line numbers of the source.
 *
 * An expression that could behave differently as a static closure is not compiled, and is evaluated at run time.
 *
 * @internal Used by {@see ContractRewriter}
 */
final class ContractCompiler
{
    private const string PREFIX = '<?php return (';

    private const string SUFFIX = "\n);";

    /**
     * Functions that read or change the local variables of the caller
     */
    private const array SCOPE_FUNCTIONS = ['compact', 'extract', 'get_defined_vars', 'func_get_args', 'func_get_arg', 'func_num_args'];

    private ?Parser $parser = null;

    /**
     * Returns the closure source code for an expression, or null if the expression must be evaluated at run time
     *
     * @param string            $expression PHP expression of the contract
     * @param list<string>|null $parameters Parameter names of the method, or null for a class invariant
     * @param bool              $isStatic   Whether the method is static
     * @param bool              $withResult Whether `$__result` and `$__old` are passed (post-conditions)
     */
    public function compile(string $expression, ?array $parameters, bool $isStatic, bool $withResult): ?string
    {
        $signature = $this->buildSignature($parameters, $withResult);
        if ($signature === null) {
            return null;
        }
        $code = self::PREFIX . $expression . self::SUFFIX;
        try {
            $statements = $this->getParser()->parse($code);
        } catch (Error) {
            // Syntax errors are reported by the run time evaluation, with the contract and the method
            return null;
        }
        if ($statements === null || \count($statements) !== 1 || !$statements[0] instanceof Return_) {
            return null;
        }
        $tokens = $this->getParser()->getTokens();
        $edits  = $this->collectEdits($statements[0], $parameters === null, $isStatic);
        if ($edits === null) {
            return null;
        }

        $body = '';
        foreach ($tokens as $position => $token) {
            // The parser ends the list with a sentinel token
            if ($token->id !== 0) {
                $body .= $edits[$position] ?? self::flatten($token);
            }
        }
        $body = \rtrim(\substr($body, \strlen(self::PREFIX), -\strlen(\ltrim(self::SUFFIX))));
        // A newline inside a string literal can not be flattened, it would shift the lines of the file
        if (\str_contains($body, "\n") || \str_contains($body, "\r")) {
            return null;
        }

        $closure = 'static function (' . $signature . ') { return (' . $body . '); }';

        // An invalid closure would break the whole woven file, so it is checked once more
        return $this->isSingleClosure($closure) ? $closure : null;
    }

    private function isSingleClosure(string $code): bool
    {
        try {
            $statements = $this->getParser()->parse('<?php ' . $code . ';');
        } catch (Error) {
            return false;
        }

        return $statements !== null && \count($statements) === 1 && $statements[0] instanceof Expression
            && $statements[0]->expr instanceof Closure && $statements[0]->expr->static;
    }

    /**
     * @param list<string>|null $parameters
     */
    private function buildSignature(?array $parameters, bool $withResult): ?string
    {
        if ($parameters === null) {
            // Invariants are evaluated with the arguments of whichever method ran, they are not part of the closure
            return '$__this, $__result = null, ...$__arguments';
        }
        $reserved = ['__this', '__result', '__old', '__arguments'];
        if (\array_intersect($parameters, $reserved) !== []) {
            return null;
        }
        $signature = ['$__this'];
        foreach ($parameters as $parameter) {
            $signature[] = '$' . $parameter;
        }
        if ($withResult) {
            $signature[] = '$__result = null';
            $signature[] = '$__old = null';
        }
        // Absorbs arguments of an overriding method that declares more parameters
        $signature[] = '...$__arguments';

        return \implode(', ', $signature);
    }

    /**
     * Returns the replacement text for tokens, by token position, or null if the expression can not be compiled
     *
     * @return array<int, string>|null
     */
    private function collectEdits(Return_ $statement, bool $isInvariant, bool $isStatic): ?array
    {
        $edits  = [];
        $finder = new NodeFinder();
        $nodes  = $finder->find([$statement], static fn(): bool => true);
        // Parameters of arrow functions are local to the expression
        $localVariables = [];
        foreach ($nodes as $node) {
            if ($node instanceof ArrowFunction) {
                foreach ($node->params as $param) {
                    if ($param->var instanceof Variable && \is_string($param->var->name)) {
                        $localVariables[$param->var->name] = true;
                    }
                }
            }
        }

        foreach ($nodes as $node) {
            if (!self::isCompilable($node, $isInvariant, $isStatic, $localVariables)) {
                return null;
            }
            if ($node instanceof Variable && $node->name === 'this') {
                $edits[$node->getStartTokenPos()] = '$__this';
            } elseif ($node instanceof Name && !$node instanceof Name\FullyQualified && !self::isKeyword($node)) {
                $edits[$node->getStartTokenPos()] = '\\' . $node->toString();
            }
        }

        return $edits;
    }

    /**
     * Checks that a node means the same in a static closure as in a closure bound to the object
     *
     * @param array<string, true> $localVariables
     */
    private static function isCompilable(Node $node, bool $isInvariant, bool $isStatic, array $localVariables): bool
    {
        if ($node instanceof Variable) {
            if (!\is_string($node->name) || $node->name === '__this' || ($isStatic && $node->name === 'this')) {
                return false;
            }
            // An invariant may only use the object and the result: other variables come from the called method
            if ($isInvariant && !\in_array($node->name, ['this', '__result'], true) && !isset($localVariables[$node->name])) {
                return false;
            }
        }
        // `static` is the class of the object only in a closure bound to it, and self::method() may forward $this
        if (!$isStatic) {
            if ($node instanceof Name && $node->toLowerString() === 'static') {
                return false;
            }
            if ($node instanceof StaticCall && $node->class instanceof Name && $node->class->isSpecialClassName()) {
                return false;
            }
        }
        if ($node instanceof FuncCall && $node->name instanceof Name
            && \in_array($node->name->toLowerString(), self::SCOPE_FUNCTIONS, true)
        ) {
            return false;
        }

        // A closure would need `$this`, magic constants describe the place of the code
        return !$node instanceof Closure && !$node instanceof MagicConst;
    }

    /**
     * `self`, `parent`, `static`, `true`, `false` and `null` keep their meaning without a leading backslash
     */
    private static function isKeyword(Name $name): bool
    {
        return $name->isSpecialClassName() || \in_array($name->toLowerString(), ['true', 'false', 'null'], true);
    }

    /**
     * Removes comments and replaces newlines in whitespace, so the closure takes a single line
     */
    private static function flatten(Token $token): string
    {
        return match ($token->id) {
            \T_COMMENT, \T_DOC_COMMENT => ' ',
            \T_WHITESPACE              => \str_replace(["\r\n", "\r", "\n"], ' ', $token->text),
            default                    => $token->text,
        };
    }

    private function getParser(): Parser
    {
        return $this->parser ??= new ParserFactory()->createForHostVersion();
    }
}
