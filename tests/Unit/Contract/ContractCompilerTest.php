<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Contract;

use PhpDeal\Contract\ContractCompiler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContractCompiler::class)]
final class ContractCompilerTest extends TestCase
{
    private ContractCompiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ContractCompiler();
    }

    public function testPreconditionTakesTheParameters(): void
    {
        self::assertSame(
            'static function ($__this, $amount, $rest, ...$__arguments) { return ($amount > 0); }',
            $this->compiler->compile('$amount > 0', ['amount', 'rest'], false, false),
        );
    }

    public function testPostconditionTakesTheResultAndTheOldState(): void
    {
        self::assertSame(
            'static function ($__this, $amount, $__result = null, $__old = null, ...$__arguments) {'
            . ' return ($__this->value === $__old->value + $amount); }',
            $this->compiler->compile('$this->value === $__old->value + $amount', ['amount'], false, true),
        );
    }

    public function testInvariantTakesTheObjectAndTheResult(): void
    {
        self::assertSame(
            'static function ($__this, $__result = null, ...$__arguments) { return ($__this->balance >= 0); }',
            $this->compiler->compile('$this->balance >= 0', null, false, true),
        );
    }

    #[TestWith(['Assert\Assertion::integer($x)', '\Assert\Assertion::integer($x)'])]
    #[TestWith(['\Assert\Assertion::integer($x)', '\Assert\Assertion::integer($x)'])]
    #[TestWith(['namespace\Money::isValid($x)', '\Money::isValid($x)'])]
    #[TestWith(['count($x) > PHP_INT_SIZE', '\count($x) > \PHP_INT_SIZE'])]
    #[TestWith(['$x instanceof DateTimeImmutable', '$x instanceof \DateTimeImmutable'])]
    #[TestWith(['$x === null || $x === TRUE', '$x === null || $x === TRUE'])]
    #[TestWith(['self::LIMIT > $x', 'self::LIMIT > $x'])]
    #[TestWith(['"$this->name" === $x', '"$__this->name" === $x'])]
    #[TestWith(['\array_all($x, fn($i) => $i > $this->min)', '\array_all($x, fn($i) => $i > $__this->min)'])]
    #[TestWith(['$x > 0 // must be positive', '$x > 0'])]
    #[TestWith(["\$x > 0\n    && \$x < 10", '$x > 0     && $x < 10'])]
    #[TestWith(['$x /* inline */ > 0', '$x   > 0'])]
    #[TestWith(['\'$this\' === $x', '\'$this\' === $x'])]
    public function testExpressionKeepsItsMeaningOutsideOfEval(string $expression, string $body): void
    {
        self::assertSame(
            'static function ($__this, $x, ...$__arguments) { return (' . $body . '); }',
            $this->compiler->compile($expression, ['x'], false, false),
        );
    }

    public function testStaticMethodKeepsStatic(): void
    {
        self::assertSame(
            'static function ($__this, ...$__arguments) { return (static::create() instanceof self); }',
            $this->compiler->compile('static::create() instanceof self', [], true, false),
        );
    }

    #[TestWith(['$amount >'], 'syntax error')]
    #[TestWith(['1); (2'], 'two statements')]
    #[TestWith(['1 ?> <?php (1'], 'inline HTML')]
    #[TestWith(['$__this !== null'], 'reserved variable')]
    #[TestWith(['$$name > 0'], 'variable variable')]
    #[TestWith(['\count(\get_defined_vars()) > 0'], 'scope function')]
    #[TestWith(['(function () { return true; })()'], 'closure that would need $this')]
    #[TestWith(['__CLASS__ !== ""'], 'magic constant')]
    #[TestWith(['static::LIMIT > 0'], 'late static binding')]
    #[TestWith(['self::isValid($amount)'], 'call that may forward $this')]
    #[TestWith(["\$name !== \"a\nb\""], 'newline in a string')]
    public function testExpressionThatMayBehaveDifferentlyIsNotCompiled(string $expression): void
    {
        self::assertNull($this->compiler->compile($expression, ['amount', 'name'], false, false));
    }

    public function testThisInStaticMethodIsNotCompiled(): void
    {
        self::assertNull($this->compiler->compile('$this->limit > 0', [], true, false));
    }

    public function testInvariantWithMethodArgumentsIsNotCompiled(): void
    {
        self::assertNull($this->compiler->compile('$amount > 0', null, false, true));
        self::assertNotNull($this->compiler->compile('\array_all($this->items, fn($item) => $item > 0)', null, false, true));
    }

    public function testReservedParameterNameIsNotCompiled(): void
    {
        self::assertNull($this->compiler->compile('$__result > 0', ['__result'], false, false));
    }
}
