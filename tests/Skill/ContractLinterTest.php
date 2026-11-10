<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Skill;

use PhpDeal\Skill\ContractLinter;
use PhpDeal\Skill\Issue;
use PhpDeal\Skill\Severity;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests of skills/design-by-contract/scripts/lint-contracts.php
 */
#[CoversNothing]
final class ContractLinterTest extends TestCase
{
    private const string ROOT   = __DIR__ . '/../..';
    private const string SCRIPT = self::ROOT . '/skills/design-by-contract/scripts/lint-contracts.php';

    public function testCorrectContractsPass(): void
    {
        $issues = self::lint(<<<'PHP'
            #[Invariant('$this->total >= 0')]
            #[Invariant('\count(\array_filter($this->items, static fn(int $item): bool => $item < 0)) === 0')]
            final class Cart
            {
                private const int LIMIT = 10;
                public static int $fee = 1;
                private array $items = [];
                private int $total = 0;

                #[Verify('$quantity > 0 && $quantity <= self::LIMIT')] // a trailing comment
                #[Verify(expression: '$price >= self::$fee')]
                #[Verify('$tags === [] || \array_is_list($tags)')]
                #[Ensure('$this->total === $__old->total + $quantity * $price')]
                #[Ensure('$__result instanceof \Countable || $__result === null')]
                public function add(int $quantity, int $price, string ...$tags): ?\Countable
                {
                    return null;
                }

                #[Verify('\App\Money::isValid($amount) && $amount !== \PHP_INT_MAX')]
                #[Verify('\in_array($amount, [1, 2], true) && self::rand() > 0 && $amount > new \DateTimeImmutable("2020-01-01")->getTimestamp()')]
                #[Ensure('$__result >= 0')]
                public static function round(int $amount = 0): int
                {
                    return $amount;
                }
            }
            PHP);

        self::assertSame([], $issues);
    }

    /**
     * @param list<string> $expected Messages, prefixed by the severity
     */
    #[DataProvider('brokenContracts')]
    public function testMistakesAreReported(string $code, array $expected): void
    {
        $messages = \array_map(
            static fn(Issue $issue): string => $issue->severity->value . ': ' . $issue->message,
            self::lint($code),
        );

        self::assertSame($expected, $messages);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function brokenContracts(): iterable
    {
        yield 'syntax error' => [
            "class A { #[Verify('\$amount >')] public function f(int \$amount): void {} }",
            ['error: Syntax error: syntax error, unexpected token ")"'],
        ];
        yield 'empty expression' => [
            "class A { #[Verify(' ')] public function f(): void {} }",
            ['error: The expression is empty'],
        ];
        yield 'misspelled parameter' => [
            "class A { #[Verify('\$amout > 0')] public function f(int \$amount): void {} }",
            ['error: Unknown variable $amout in f(), available: $amount, $this'],
        ];
        yield 'result in a precondition' => [
            "class A { #[Verify('\$__result > 0')] public function f(): int { return 1; } }",
            ['error: $__result is not known before the call: check the result with #[Ensure]'],
        ];
        yield 'result of a void method' => [
            "class A { #[Ensure('\$__result === null')] public function f(): void {} }",
            ['warning: $__result is always null: f() returns void'],
        ];
        yield 'old state in a precondition' => [
            "class A { #[Verify('\$__old->a === 1')] public function f(): void {} }",
            ['error: $__old is available only in #[Ensure], not in #[Verify]'],
        ];
        yield 'old state in a static method' => [
            "class A { #[Ensure('\$__old !== null')] public static function f(): void {} }",
            ['error: $__old is not available in the static method f()'],
        ];
        yield 'this in a static method' => [
            "class A { #[Verify('\$this->a > 0')] final public static function f(): void {} }",
            ['error: $this is not available in the static method f(): use self:: or static::'],
        ];
        yield 'argument in an invariant' => [
            "#[Invariant('\$amount > 0')] class A {}",
            ['error: Unknown variable $amount: an invariant can use only $this and the members of A'],
        ];
        yield 'result in an invariant' => [
            "#[Invariant('\$__result !== null')] interface A {}",
            ['warning: $__result in an invariant is the result of whichever public method ran last'],
        ];
        yield 'invariant on a method' => [
            "class A { #[Invariant('true')] public function f(): void {} }",
            ['error: #[Invariant] belongs on a class or an interface, not on the method f()'],
        ];
        yield 'precondition on a class' => [
            "#[Verify('true')] final readonly class A {}",
            ['error: #[Verify] belongs on a method, not on the class A'],
        ];
        yield 'precondition on a property' => [
            "class A { #[Verify('true')] public int \$a = 1; }",
            ['error: #[Verify] belongs on a method, not on "int"'],
        ];
        yield 'interpolated string' => [
            'class A { #[Verify("$a > 0")] public function f(int $a): void {} }',
            ['error: #[Verify] uses a double-quoted string, PHP would try to interpolate its variables: use single quotes'],
        ];
        yield 'double-quoted string' => [
            'class A { #[Verify("true")] public function f(): void {} }',
            ['warning: Write contracts in single quotes, as in the documentation'],
        ];
        yield 'expression that can not be read' => [
            'class A { #[Verify(self::RULE)] public function f(): void {} }',
            ['warning: #[Verify] is not a single string literal and can not be checked'],
        ];
        yield 'unqualified function' => [
            "class A { #[Verify('is_int(\$a)')] public function f(int \$a): void {} }",
            ['warning: Write global functions fully qualified: \is_int()'],
        ];
        yield 'imported function' => [
            "class A { #[Verify('isPositive(\$a)')] public function f(int \$a): void {} }",
            ['warning: isPositive() is not a PHP function: a contract does not see "use function" imports, write the fully qualified name'],
        ];
        yield 'imported class' => [
            "class A { #[Verify('Money::isValid(\$a)')] public function f(int \$a): void {} }",
            ['error: Class Money would be looked up in the global namespace: a contract does not see "use" imports, write the fully qualified name'],
        ];
        yield 'unqualified global class' => [
            "class A { #[Verify('\$a instanceof DateTimeInterface')] public function f(object \$a): void {} }",
            ['warning: Write class names fully qualified: \DateTimeInterface'],
        ];
        yield 'assignment' => [
            "class A { #[Ensure('\$this->a = 1')] public function f(): void {} }",
            ['warning: A contract must not change state, "=" found'],
        ];
        yield 'increment' => [
            "class A { #[Ensure('++\$this->a > 1')] public function f(): void {} }",
            ['warning: A contract must not change state, "++" found'],
        ];
        yield 'superglobal' => [
            "class A { #[Verify('\$_SERVER !== []')] public function f(): void {} }",
            ['warning: A contract should not depend on $_SERVER'],
        ];
        yield 'result in a constructor' => [
            "class A { #[Ensure('\$__result !== null')] public function __construct() {} }",
            ['warning: $__result is always null in a constructor: check $this instead'],
        ];
        yield 'old state in a constructor' => [
            "class A { #[Ensure('\$__old->a === 0')] public function __construct(public int \$a = 1) {} }",
            ['warning: $__old in a constructor is the object before its properties are set'],
        ];
        yield 'current time' => [
            "class A { #[Verify('\$at < new \\DateTimeImmutable()')] public function f(\\DateTimeImmutable \$at): void {} }",
            ['warning: The contract reads the current time, so it can fail at random: pass the time in as an argument'],
        ];
        yield 'random value' => [
            "class A { #[Verify('\\random_int(0, 1) === 0')] public function f(): void {} }",
            ['warning: random_int() returns a different value on every call, so the contract can fail at random'],
        ];
        yield 'loose in_array' => [
            "class A { #[Verify('\\in_array(\$c, [1, 2])')] public function f(int \$c): void {} }",
            ['warning: in_array() compares loosely without its strict argument: pass true'],
        ];
        yield 'contract without expression' => [
            'class A { #[Verify] public function f(): void {} }',
            ['error: #[Verify] needs an expression'],
        ];
    }

    public function testFilesWithoutPhpDealAreSkipped(): void
    {
        $linter = new ContractLinter();

        self::assertSame([], $linter->lintCode("<?php #[Verify('\$broken >')] function f() {}"));
        self::assertSame(0, $linter->getContractCount());
    }

    public function testBrokenPhpIsReported(): void
    {
        $issues = new ContractLinter()->lintCode("<?php\nuse PhpDeal\\Attribute\\Verify;\nclass {", 'broken.php');

        self::assertCount(1, $issues);
        self::assertSame(Severity::Error, $issues[0]->severity);
        self::assertStringStartsWith('broken.php:3: error: PHP syntax error:', (string) $issues[0]);
    }

    public function testIssueNamesFileLineAndContract(): void
    {
        $issues = self::lint("class A {\n    #[Verify('\$b > 0')]\n    public function f(int \$a): void {}\n}");

        self::assertSame(
            ["file.php:10: error: #[Verify('\$b > 0')] Unknown variable \$b in f(), available: \$a, \$this"],
            \array_map(static fn(Issue $issue): string => (string) $issue, $issues),
        );
    }

    /**
     * The worked example of the skill and the demo are what agents copy: they must stay free of warnings
     */
    public function testExampleAndDemoAreClean(): void
    {
        $linter = new ContractLinter();
        $issues = $linter->lintPaths([self::ROOT . '/skills/design-by-contract/examples', self::ROOT . '/demo']);

        self::assertSame([], \array_map(static fn(Issue $issue): string => (string) $issue, $issues));
        self::assertGreaterThan(20, $linter->getContractCount());
    }

    public function testCommandLineReportsAndFails(): void
    {
        [$exitCode, $output] = self::runScript(self::ROOT . '/tests/Fixtures/Verify/Account.php');

        self::assertSame(1, $exitCode);
        self::assertStringContainsString("Account.php:47: error: #[Verify('\$amount >')] Syntax error", $output);
        self::assertStringContainsString('2 error(s), 0 warning(s)', $output);
    }

    public function testCommandLinePassesCleanCode(): void
    {
        [$exitCode, $output] = self::runScript('--strict', self::ROOT . '/demo');

        self::assertSame(0, $exitCode);
        self::assertSame("7 contract(s) checked: 0 error(s), 0 warning(s)\n", $output);
    }

    public function testCommandLineNeedsAPath(): void
    {
        self::assertSame(2, self::runScript()[0]);
        self::assertSame(2, self::runScript(self::ROOT . '/does-not-exist')[0]);
    }

    /**
     * @return list<Issue>
     */
    private static function lint(string $code): array
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nuse PhpDeal\\Attribute\\{Ensure, Invariant, Verify};\n\n";

        return new ContractLinter()->lintCode($header . $code, 'file.php');
    }

    /**
     * @return array{int, string}
     */
    private static function runScript(string ...$arguments): array
    {
        $process = \proc_open([\PHP_BINARY, self::SCRIPT, ...\array_values($arguments)], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $output = (string) \stream_get_contents($pipes[1]);
        \fclose($pipes[1]);
        \fclose($pipes[2]);

        return [\proc_close($process), $output];
    }
}
