<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Contract;

use ParseError;
use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;
use PhpDeal\Contract\BoundContract;
use PhpDeal\Contract\ContractEvaluator;
use PhpDeal\Contract\ContractKind;
use PhpDeal\Exception\InvalidContractException;
use PhpDeal\Exception\InvariantViolation;
use PhpDeal\Exception\PostconditionViolation;
use PhpDeal\Exception\PreconditionViolation;
use PhpDeal\Tests\Unit\Stub\FakeInvocation;
use PhpDeal\Tests\Unit\Stub\Square;
use PhpDeal\Tests\Unit\Stub\Status;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use RuntimeException;
use stdClass;
use Throwable;

#[CoversClass(ContractEvaluator::class)]
#[CoversClass(ContractKind::class)]
final class ContractEvaluatorTest extends TestCase
{
    private ContractEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new ContractEvaluator();
    }

    public function testArgumentsAreKeyedByName(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'withParameters', 1, 2, 3, 4);

        self::assertSame(['a' => 1, 'b' => 2, 'rest' => [3, 4]], $this->evaluator->getArguments($invocation));
    }

    public function testMissingArgumentsUseDefaults(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'withParameters', 1);

        self::assertSame(['a' => 1, 'b' => 5, 'rest' => []], $this->evaluator->getArguments($invocation));
    }

    public function testObjectDefaultsAreNotSharedBetweenCalls(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'withDefaults');

        $first  = $this->evaluator->getArguments($invocation);
        $second = $this->evaluator->getArguments($invocation);

        self::assertInstanceOf(stdClass::class, $first['options']);
        self::assertNotSame($first['options'], $second['options']);
        self::assertSame(Status::Active, $second['status']);
        self::assertSame([1], $second['list']);
    }

    public function testOldStateIsAClone(): void
    {
        $square = new Square();

        $clone = $this->evaluator->cloneState($square);

        self::assertInstanceOf(Square::class, $clone);
        self::assertNotSame($square, $clone);
        self::assertNull($this->evaluator->cloneState(Status::Active));
    }

    public function testThisInStringOfStaticMethodIsAllowed(): void
    {
        $invocation = FakeInvocation::onStatic(Square::class, 'create');
        $contracts  = [new BoundContract(new Ensure('\'$this\' !== "" && "\\$this" !== ""'), Square::class)];

        $this->evaluator->check($invocation, $contracts, [], ContractKind::Postcondition);

        $this->addToAssertionCount(1);
    }

    public function testHoldingContractsPass(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'scale', 2.0);
        $contracts  = [
            new BoundContract(new Verify('$factor === 2.0'), Square::class),
            new BoundContract(new Verify('$this->size === 2'), Square::class),
            new BoundContract(new Verify('null'), Square::class),
        ];

        $this->evaluator->check($invocation, $contracts, ['factor' => 2.0], ContractKind::Precondition);

        self::assertFalse($this->evaluator->isEvaluating());
    }

    /**
     * @param class-string<Throwable> $violationClass
     */
    #[TestWith([ContractKind::Precondition, PreconditionViolation::class])]
    #[TestWith([ContractKind::Postcondition, PostconditionViolation::class])]
    #[TestWith([ContractKind::Invariant, InvariantViolation::class])]
    public function testKindSelectsTheViolation(ContractKind $kind, string $violationClass): void
    {
        $invocation = FakeInvocation::on(new Square(), 'scale', 2.0);

        $this->expectException($violationClass);
        $this->evaluator->check($invocation, [new BoundContract(new Ensure('false'), Square::class)], [], $kind);
    }

    public function testVariablesCanNotReplaceThis(): void
    {
        $square     = new Square();
        $invocation = FakeInvocation::on($square, 'scale', 2.0);
        $contracts  = [new BoundContract(new Invariant('$this instanceof \PhpDeal\Tests\Unit\Stub\Square'), Square::class)];

        $this->evaluator->check($invocation, $contracts, ['this' => new stdClass()], ContractKind::Invariant);

        $this->addToAssertionCount(1);
    }

    public function testEvaluationFlagIsSetWhileExpressionRuns(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'scale', 2.0);
        $contracts  = [new BoundContract(new Verify('$evaluator->isEvaluating()'), Square::class)];

        $this->evaluator->check($invocation, $contracts, ['evaluator' => $this->evaluator], ContractKind::Precondition);

        self::assertFalse($this->evaluator->isEvaluating());
    }

    public function testEvaluationFlagIsResetAfterFailure(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'scale', 2.0);
        $contracts  = [new BoundContract(new Verify('throw new \RuntimeException("boom")'), Square::class)];

        try {
            $this->evaluator->check($invocation, $contracts, [], ContractKind::Precondition);
            self::fail('A violation was expected');
        } catch (PreconditionViolation $violation) {
            self::assertInstanceOf(RuntimeException::class, $violation->getPrevious());
            self::assertStringEndsWith(': boom', $violation->getMessage());
        }
        self::assertFalse($this->evaluator->isEvaluating());
    }

    public function testStaticInvocationIsEvaluatedInClassScope(): void
    {
        $invocation = FakeInvocation::onStatic(Square::class, 'create');
        $contracts  = [new BoundContract(new Ensure('self::class === \PhpDeal\Tests\Unit\Stub\Square::class'), Square::class)];

        $this->evaluator->check($invocation, $contracts, [], ContractKind::Postcondition);

        $this->addToAssertionCount(1);
    }

    public function testThisInStaticInvocationIsInvalid(): void
    {
        $invocation = FakeInvocation::onStatic(Square::class, 'create');

        $this->expectException(InvalidContractException::class);
        $this->expectExceptionMessage('Contract "$this->size > 0" of ' . Square::class . '::create() uses $this');
        $this->evaluator->check(
            $invocation,
            [new BoundContract(new Ensure('$this->size > 0'), Square::class)],
            [],
            ContractKind::Postcondition,
        );
    }

    public function testCompiledContractIsCalledInTheScopeOfTheContract(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'scale', 2.0);
        // The expression is not evaluated when a compiled closure is given
        $contract = new BoundContract(
            new Verify('false', static fn(Square $__this, float $factor): bool => $factor === 2.0),
            Square::class,
        );

        $this->evaluator->check($invocation, [$contract], ['factor' => 2.0], ContractKind::Precondition);

        self::assertNotNull($contract->check);
        self::assertSame(Square::class, new ReflectionFunction($contract->check)->getClosureScopeClass()?->name);
        self::assertFalse($this->evaluator->isEvaluating());
    }

    public function testCompiledContractReportsTheExpression(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'scale', 2.0);
        $contracts  = [new BoundContract(new Ensure('$__result > 5', static fn($__this, $__result): bool => $__result > 5), Square::class)];

        $this->expectException(PostconditionViolation::class);
        $this->expectExceptionMessage('Postcondition "$__result > 5" violated');
        $this->evaluator->check($invocation, $contracts, ['__result' => 4.0], ContractKind::Postcondition);
    }

    public function testCompiledContractGetsNoObjectInStaticMethod(): void
    {
        $invocation = FakeInvocation::onStatic(Square::class, 'create');
        $contracts  = [new BoundContract(new Ensure('true', static fn($__this): bool => $__this === null), Square::class)];

        $this->evaluator->check($invocation, $contracts, [], ContractKind::Postcondition);

        $this->addToAssertionCount(1);
    }

    public function testSyntaxErrorIsInvalid(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'scale', 2.0);

        try {
            $this->evaluator->check($invocation, [new BoundContract(new Verify('1 +'), Square::class)], [], ContractKind::Precondition);
            self::fail('An invalid contract was expected');
        } catch (InvalidContractException $exception) {
            self::assertInstanceOf(ParseError::class, $exception->getPrevious());
        }
    }

    public function testNonBooleanResultIsInvalid(): void
    {
        $invocation = FakeInvocation::on(new Square(), 'scale', 2.0);

        $this->expectException(InvalidContractException::class);
        $this->expectExceptionMessage('string returned');
        $this->evaluator->check($invocation, [new BoundContract(new Verify('"yes"'), Square::class)], [], ContractKind::Precondition);
    }
}
