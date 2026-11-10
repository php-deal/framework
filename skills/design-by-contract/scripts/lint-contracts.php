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

namespace PhpDeal\Skill;

use FilesystemIterator;
use ParseError;
use PhpToken;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Static checker for the contract expressions of php-deal/framework
 *
 * Contract expressions are strings, so neither PHP nor PHPStan see mistakes in them until a woven method is called.
 * The checker reads `#[Verify]`, `#[Ensure]` and `#[Invariant]` attributes from PHP files without loading or
 * weaving them, and reports syntax errors, unknown variables, `$__old`/`$__result` in the wrong kind of contract,
 * `$this` in static methods and names that `use` imports would not resolve.
 *
 * Usage: php lint-contracts.php [--strict] <file-or-directory>...
 */
final class ContractLinter
{
    private const array CONTRACTS = ['Verify', 'Ensure', 'Invariant'];

    private const array MODIFIERS = [\T_PUBLIC, \T_PROTECTED, \T_PRIVATE, \T_STATIC, \T_ABSTRACT, \T_FINAL, \T_READONLY];

    private const array CLASS_LIKE = [\T_CLASS, \T_INTERFACE, \T_TRAIT, \T_ENUM];

    private const array SUPERGLOBALS = ['GLOBALS', '_SERVER', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV'];

    private const array SKIPPED_DIRECTORIES = ['vendor', 'cache', 'node_modules', '.git'];

    private const array MUTATIONS = [
        \T_INC, \T_DEC, \T_PLUS_EQUAL, \T_MINUS_EQUAL, \T_MUL_EQUAL, \T_DIV_EQUAL, \T_CONCAT_EQUAL, \T_MOD_EQUAL,
        \T_POW_EQUAL, \T_COALESCE_EQUAL, \T_AND_EQUAL, \T_OR_EQUAL, \T_XOR_EQUAL, \T_SL_EQUAL, \T_SR_EQUAL, \T_UNSET,
    ];

    private const string PREFIX = '<?php return static function (array $__contractVariables): mixed { return (';

    /**
     * The closing parenthesis is on its own line, so a trailing "//" comment can not swallow it
     */
    private const string SUFFIX = "\n); };";

    private const int SUFFIX_LENGTH = 4;

    private int $contractCount = 0;

    /**
     * Number of contract attributes found by the previous calls
     */
    public function getContractCount(): int
    {
        return $this->contractCount;
    }

    /**
     * Checks every PHP file in the given files and directories
     *
     * @param list<string> $paths
     *
     * @return list<Issue>
     */
    public function lintPaths(array $paths): array
    {
        $issues = [];
        foreach ($paths as $path) {
            foreach (self::findFiles($path) as $file) {
                $code = \file_get_contents($file);
                if ($code === false) {
                    $issues[] = new Issue($file, 0, Severity::Error, 'The file can not be read');
                    continue;
                }
                \array_push($issues, ...$this->lintCode($code, $file));
            }
        }

        return $issues;
    }

    /**
     * Checks the contracts declared in a piece of PHP code
     *
     * @return list<Issue>
     */
    public function lintCode(string $code, string $file = 'code.php'): array
    {
        if (!\str_contains($code, 'PhpDeal\\Attribute')) {
            return [];
        }
        try {
            $tokens = self::significant(PhpToken::tokenize($code, \TOKEN_PARSE));
        } catch (ParseError $error) {
            return [new Issue($file, $error->getLine(), Severity::Error, 'PHP syntax error: ' . $error->getMessage())];
        }

        $issues  = [];
        $pending = [];
        $count   = \count($tokens);
        for ($index = 0; $index < $count; ++$index) {
            $token = $tokens[$index];
            if ($token->id === \T_ATTRIBUTE) {
                $index = $this->readAttributeGroup($tokens, $index, $file, $pending, $issues);
                continue;
            }
            if ($pending === [] || \in_array($token->id, self::MODIFIERS, true)) {
                continue;
            }
            $target = self::readTarget($tokens, $index);
            foreach ($pending as $contract) {
                \array_push($issues, ...$this->check($contract, $target, $file));
            }
            $pending = [];
        }

        return $issues;
    }

    /**
     * Reads one `#[...]` group and collects its contract attributes
     *
     * @param list<PhpToken>      $tokens
     * @param list<FoundContract> $pending
     * @param list<Issue>         $issues
     *
     * @return int Index of the closing bracket of the group
     */
    private function readAttributeGroup(array $tokens, int $start, string $file, array &$pending, array &$issues): int
    {
        $depth = 1;
        $count = \count($tokens);
        $index = $start + 1;
        for (; $index < $count && $depth > 0; ++$index) {
            $token = $tokens[$index];
            if ($token->text === '[' || $token->text === '(') {
                ++$depth;
                continue;
            }
            if ($token->text === ']' || $token->text === ')') {
                --$depth;
                continue;
            }
            $previous = $tokens[$index - 1]->text;
            if ($depth !== 1 || ($previous !== '[' && $previous !== ',' && $tokens[$index - 1]->id !== \T_ATTRIBUTE)) {
                continue;
            }
            $kind = self::contractKind($token);
            if ($kind === null) {
                continue;
            }
            ++$this->contractCount;
            $contract = self::readArgument($tokens, $index + 1, $kind, $token->line);
            if ($contract instanceof Issue) {
                $issues[] = $contract->in($file);
            } else {
                $pending[] = $contract;
            }
        }

        return $index - 1;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private static function readArgument(array $tokens, int $index, string $kind, int $line): FoundContract|Issue
    {
        if (($tokens[$index]->text ?? '') !== '(') {
            return new Issue('', $line, Severity::Error, "#[{$kind}] needs an expression");
        }
        ++$index;
        if (($tokens[$index]->text ?? '') === 'expression' && ($tokens[$index + 1]->text ?? '') === ':') {
            $index += 2;
        }
        $argument = $tokens[$index] ?? null;
        $next     = $tokens[$index + 1]->text ?? '';
        if ($argument !== null && $argument->text === '"') {
            return new Issue('', $line, Severity::Error, "#[{$kind}] uses a double-quoted string, PHP would try to interpolate its variables: use single quotes");
        }
        if ($argument === null || $argument->id !== \T_CONSTANT_ENCAPSED_STRING || ($next !== ')' && $next !== ',')) {
            return new Issue('', $line, Severity::Warning, "#[{$kind}] is not a single string literal and can not be checked");
        }

        $literal = $argument->text;
        if ($literal[0] === '"') {
            return new FoundContract($kind, \stripcslashes(\substr($literal, 1, -1)), $line, true);
        }
        $expression = \preg_replace('/\\\\([\\\\\'])/', '$1', \substr($literal, 1, -1)) ?? '';

        return new FoundContract($kind, $expression, $line, false);
    }

    /**
     * Describes the declaration that follows the attributes
     *
     * @param list<PhpToken> $tokens
     */
    private static function readTarget(array $tokens, int $index): Target
    {
        $token    = $tokens[$index];
        $isStatic = false;
        for ($back = $index - 1; $back >= 0 && \in_array($tokens[$back]->id, self::MODIFIERS, true); --$back) {
            $isStatic = $isStatic || $tokens[$back]->id === \T_STATIC;
        }
        if (\in_array($token->id, self::CLASS_LIKE, true)) {
            return new Target(TargetType::ClassLike, $tokens[$index + 1]->text ?? '', $token->text);
        }
        if ($token->id !== \T_FUNCTION) {
            return new Target(TargetType::Other, $token->text, $token->text);
        }

        $index += ($tokens[$index + 1]->text ?? '') === '&' ? 2 : 1;
        $name       = $tokens[$index]->text ?? '';
        $parameters = [];
        $depth      = 0;
        $count      = \count($tokens);
        for (++$index; $index < $count; ++$index) {
            $text = $tokens[$index]->text;
            if ($text === '(' || $text === '[' || $tokens[$index]->id === \T_ATTRIBUTE) {
                ++$depth;
            } elseif ($text === ')' || $text === ']') {
                --$depth;
                if ($depth === 0) {
                    break;
                }
            } elseif ($tokens[$index]->id === \T_VARIABLE) {
                $parameters[] = \substr($text, 1);
            }
        }
        $returnType = '';
        if (($tokens[$index + 1]->text ?? '') === ':') {
            for ($index += 2; $index < $count && $tokens[$index]->text !== '{' && $tokens[$index]->text !== ';'; ++$index) {
                $returnType .= $tokens[$index]->text;
            }
        }

        return new Target(TargetType::Method, $name, 'function', $parameters, $isStatic, \strtolower($returnType));
    }

    /**
     * @return list<Issue>
     */
    private function check(FoundContract $contract, Target $target, string $file): array
    {
        $issues = [];
        $report = static function (Severity $severity, string $message) use ($contract, $file, &$issues): void {
            $issues[] = new Issue($file, $contract->line, $severity, $message, $contract->kind, $contract->expression);
        };

        if ($contract->doubleQuoted) {
            $report(Severity::Warning, 'Write contracts in single quotes, as in the documentation');
        }
        $isClassContract = $contract->kind === 'Invariant';
        if ($isClassContract && $target->type !== TargetType::ClassLike) {
            $report(Severity::Error, '#[Invariant] belongs on a class or an interface, not on ' . $target->describe());

            return $issues;
        }
        if (!$isClassContract && $target->type !== TargetType::Method) {
            $report(Severity::Error, "#[{$contract->kind}] belongs on a method, not on " . $target->describe());

            return $issues;
        }
        if (\trim($contract->expression) === '') {
            $report(Severity::Error, 'The expression is empty');

            return $issues;
        }

        // Same wrapper as the evaluator of php-deal: a closure that returns the expression
        try {
            $tokens = self::significant(PhpToken::tokenize(self::PREFIX . $contract->expression . self::SUFFIX, \TOKEN_PARSE));
        } catch (ParseError $error) {
            $report(Severity::Error, 'Syntax error: ' . $error->getMessage());

            return $issues;
        }
        $tokens = \array_slice($tokens, self::prefixLength(), -self::SUFFIX_LENGTH);
        $locals = self::localVariables($tokens);
        $seen   = [];
        foreach ($tokens as $index => $token) {
            if ($token->id === \T_VARIABLE) {
                if (($tokens[$index - 1] ?? null)?->id === \T_DOUBLE_COLON) {
                    continue; // a static property, self::$name
                }
                $name = \substr($token->text, 1);
                if (!isset($seen[$name]) && !isset($locals[$name])) {
                    $seen[$name] = true;
                    self::checkVariable($name, $contract, $target, $report);
                }
                continue;
            }
            if ($token->text === '=' || \in_array($token->id, self::MUTATIONS, true)) {
                $report(Severity::Warning, "A contract must not change state, \"{$token->text}\" found");
            }
            if ($token->id === \T_STRING) {
                self::checkName($tokens, $index, $report);
            }
        }

        return $issues;
    }

    /**
     * @param callable(Severity, string): void $report
     */
    private static function checkVariable(string $name, FoundContract $contract, Target $target, callable $report): void
    {
        $where = $target->type === TargetType::Method ? "{$target->name}()" : $target->name;
        switch ($name) {
            case 'this':
                if ($target->isStatic) {
                    $report(Severity::Error, "\$this is not available in the static method {$where}: use self:: or static::");
                }

                return;
            case '__old':
                if ($contract->kind !== 'Ensure') {
                    $report(Severity::Error, "\$__old is available only in #[Ensure], not in #[{$contract->kind}]");
                } elseif ($target->isStatic) {
                    $report(Severity::Error, "\$__old is not available in the static method {$where}");
                }

                return;
            case '__result':
                if ($contract->kind === 'Verify') {
                    $report(Severity::Error, '$__result is not known before the call: check the result with #[Ensure]');
                } elseif ($contract->kind === 'Invariant') {
                    $report(Severity::Warning, '$__result in an invariant is the result of whichever public method ran last');
                } elseif ($target->returnType === 'void' || $target->returnType === 'never') {
                    $report(Severity::Warning, "\$__result is always null: {$where} returns {$target->returnType}");
                }

                return;
        }
        if (\in_array($name, $target->parameters, true)) {
            return;
        }
        if (\in_array($name, self::SUPERGLOBALS, true)) {
            $report(Severity::Warning, "A contract should not depend on \${$name}");

            return;
        }
        if ($contract->kind === 'Invariant') {
            $report(Severity::Error, "Unknown variable \${$name}: an invariant can use only \$this and the members of {$where}");

            return;
        }
        $available = \array_map(static fn(string $parameter): string => '$' . $parameter, $target->parameters);
        $available = [...$available, ...($target->isStatic ? [] : ['$this']), ...($contract->kind === 'Ensure' ? ['$__result', '$__old'] : [])];
        $report(Severity::Error, "Unknown variable \${$name} in {$where}, available: " . ($available === [] ? 'none' : \implode(', ', $available)));
    }

    /**
     * Reports unqualified function and class names: `use` imports do not apply inside contracts
     *
     * @param list<PhpToken>                   $tokens
     * @param callable(Severity, string): void $report
     */
    private static function checkName(array $tokens, int $index, callable $report): void
    {
        $name     = $tokens[$index]->text;
        $previous = $tokens[$index - 1] ?? null;
        $next     = $tokens[$index + 1]->text ?? '';
        if ($previous !== null && \in_array($previous->id, [\T_OBJECT_OPERATOR, \T_NULLSAFE_OBJECT_OPERATOR, \T_DOUBLE_COLON, \T_FUNCTION, \T_FN, \T_CONST], true)) {
            return;
        }
        if (\in_array(\strtolower($name), ['self', 'parent', 'static', 'true', 'false', 'null'], true)) {
            return;
        }
        if ($next === '(' && ($previous === null || $previous->id !== \T_NEW)) {
            $report(Severity::Warning, \function_exists($name)
                ? "Write global functions fully qualified: \\{$name}()"
                : "{$name}() is not a PHP function: a contract does not see \"use function\" imports, write the fully qualified name");

            return;
        }
        $isClass = $next === '::' || ($previous !== null && \in_array($previous->id, [\T_NEW, \T_INSTANCEOF], true));
        if (!$isClass) {
            return;
        }
        if (\class_exists($name, false) || \interface_exists($name, false) || \enum_exists($name, false)) {
            $report(Severity::Warning, "Write class names fully qualified: \\{$name}");

            return;
        }
        $report(Severity::Error, "Class {$name} would be looked up in the global namespace: a contract does not see \"use\" imports, write the fully qualified name");
    }

    /**
     * Variables declared inside the expression: closure and arrow function parameters, `use` lists and assignments
     *
     * @param list<PhpToken> $tokens
     *
     * @return array<string, true>
     */
    private static function localVariables(array $tokens): array
    {
        $locals = [];
        $count  = \count($tokens);
        foreach ($tokens as $index => $token) {
            if ($token->id === \T_VARIABLE && ($tokens[$index + 1]->text ?? '') === '=' && $token->text !== '$this') {
                $locals[\substr($token->text, 1)] = true;
            }
            if ($token->id !== \T_FN && $token->id !== \T_FUNCTION && $token->id !== \T_USE) {
                continue;
            }
            $depth = 0;
            for ($inner = $index + 1; $inner < $count; ++$inner) {
                $text = $tokens[$inner]->text;
                if ($text === '(') {
                    ++$depth;
                } elseif ($text === ')') {
                    if (--$depth === 0) {
                        break;
                    }
                } elseif ($depth > 0 && $tokens[$inner]->id === \T_VARIABLE) {
                    $locals[\substr($text, 1)] = true;
                } elseif ($depth === 0 && $text !== '&') {
                    break;
                }
            }
        }

        return $locals;
    }

    private static function prefixLength(): int
    {
        return \count(self::significant(PhpToken::tokenize(self::PREFIX)));
    }

    private static function contractKind(PhpToken $token): ?string
    {
        if (!\in_array($token->id, [\T_STRING, \T_NAME_QUALIFIED, \T_NAME_FULLY_QUALIFIED], true)) {
            return null;
        }
        $position = \strrpos($token->text, '\\');
        $name     = $position === false ? $token->text : \substr($token->text, $position + 1);

        return \in_array($name, self::CONTRACTS, true) ? $name : null;
    }

    /**
     * @param array<PhpToken> $tokens
     *
     * @return list<PhpToken>
     */
    private static function significant(array $tokens): array
    {
        return \array_values(\array_filter($tokens, static fn(PhpToken $token): bool => !$token->isIgnorable()));
    }

    /**
     * @return list<string>
     */
    private static function findFiles(string $path): array
    {
        if (\is_file($path)) {
            return [$path];
        }
        if (!\is_dir($path)) {
            return [];
        }
        $files    = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = \str_replace('\\', '/', \substr($file->getPathname(), \strlen($path)));
            $parts    = \explode('/', $relative);
            if (\array_intersect($parts, self::SKIPPED_DIRECTORIES) === []) {
                $files[] = $file->getPathname();
            }
        }
        \sort($files);

        return $files;
    }
}

/**
 * A contract attribute read from the source
 */
final readonly class FoundContract
{
    public function __construct(
        public string $kind,
        public string $expression,
        public int $line,
        public bool $doubleQuoted,
    ) {}
}

enum TargetType
{
    case Method;
    case ClassLike;
    case Other;
}

/**
 * The declaration a contract attribute is attached to
 */
final readonly class Target
{
    /**
     * @param list<string> $parameters Parameter names of a method, without "$"
     */
    public function __construct(
        public TargetType $type,
        public string $name,
        public string $keyword,
        public array $parameters = [],
        public bool $isStatic = false,
        public string $returnType = '',
    ) {}

    public function describe(): string
    {
        return match ($this->type) {
            TargetType::Method    => "the method {$this->name}()",
            TargetType::ClassLike => "the {$this->keyword} {$this->name}",
            TargetType::Other     => "\"{$this->name}\"",
        };
    }
}

enum Severity: string
{
    case Error   = 'error';
    case Warning = 'warning';
}

/**
 * A problem found in a contract
 */
final readonly class Issue
{
    public function __construct(
        public string $file,
        public int $line,
        public Severity $severity,
        public string $message,
        public ?string $kind = null,
        public ?string $expression = null,
    ) {}

    public function in(string $file): self
    {
        return new self($file, $this->line, $this->severity, $this->message, $this->kind, $this->expression);
    }

    public function __toString(): string
    {
        $contract = $this->kind !== null && $this->expression !== null ? " #[{$this->kind}('{$this->expression}')]" : '';

        return "{$this->file}:{$this->line}: {$this->severity->value}:{$contract} {$this->message}";
    }
}

/**
 * Command line entry point
 *
 * @param list<string> $arguments
 */
function main(array $arguments): int
{
    $strict = \in_array('--strict', $arguments, true);
    $paths  = \array_values(\array_filter($arguments, static fn(string $argument): bool => !\str_starts_with($argument, '--')));
    if ($paths === [] || \in_array('--help', $arguments, true)) {
        \fwrite(\STDERR, "Checks the php-deal contract expressions of PHP files.\n\n"
            . "Usage: php lint-contracts.php [--strict] <file-or-directory>...\n\n"
            . "  --strict  fail on warnings too\n\n"
            . "Exit code: 0 when no error is found, 1 otherwise.\n");

        return $paths === [] ? 2 : 0;
    }
    foreach ($paths as $path) {
        if (!\file_exists($path)) {
            \fwrite(\STDERR, "No such file or directory: {$path}\n");

            return 2;
        }
    }

    $linter   = new ContractLinter();
    $issues   = $linter->lintPaths($paths);
    $errors   = \count(\array_filter($issues, static fn(Issue $issue): bool => $issue->severity === Severity::Error));
    $warnings = \count($issues) - $errors;
    foreach ($issues as $issue) {
        echo $issue, \PHP_EOL;
    }
    echo \sprintf('%d contract(s) checked: %d error(s), %d warning(s)', $linter->getContractCount(), $errors, $warnings), \PHP_EOL;

    return $errors > 0 || ($strict && $warnings > 0) ? 1 : 0;
}

if (\PHP_SAPI === 'cli' && \get_included_files()[0] === __FILE__) {
    $arguments = \is_array($_SERVER['argv'] ?? null) ? $_SERVER['argv'] : [];
    exit(main(\array_values(\array_filter(\array_slice($arguments, 1), \is_string(...)))));
}
