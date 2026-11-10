<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Functional;

use Error;
use PhpDeal\Exception\ContractViolation;
use PhpDeal\Exception\InvalidContractException;
use PhpDeal\Exception\PreconditionViolation;
use PhpDeal\Tests\Fixtures\Verify\Account;
use PhpDeal\Tests\Fixtures\Verify\ChildWithInheritDoc;
use PhpDeal\Tests\Fixtures\Verify\ChildWithoutInheritance;
use PhpDeal\Tests\Fixtures\Verify\PrivateChild;
use PhpDeal\Tests\Fixtures\Verify\RangeWithInheritDoc;
use PhpDeal\Tests\Fixtures\Verify\RangeWithoutInheritance;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(\PhpDeal\Aspect\PreconditionCheckerAspect::class)]
final class VerifyTest extends TestCase
{
    private Account $account;

    protected function setUp(): void
    {
        $this->account = new Account();
    }

    #[DoesNotPerformAssertions]
    public function testValidPreconditionLetsTheCallThrough(): void
    {
        $this->account->numeric(-200);
    }

    public function testPreconditionIsCheckedBeforeTheBody(): void
    {
        $this->expectException(PreconditionViolation::class);
        $this->expectExceptionMessage('Precondition "$amount > $this->limit" violated in ' . Account::class . '->aboveLimit(42)');

        $this->account->aboveLimit(42);
    }

    #[DoesNotPerformAssertions]
    public function testContractCanReadPrivateState(): void
    {
        $this->account->aboveLimit(50);
    }

    #[DoesNotPerformAssertions]
    public function testAssertionLibraryCanBeUsed(): void
    {
        $this->account->withAssertion(100);
    }

    public function testFailedAssertionIsReportedAsViolation(): void
    {
        try {
            $this->account->withAssertion(1);
            self::fail('A violation was expected');
        } catch (PreconditionViolation $violation) {
            self::assertInstanceOf(\Assert\InvalidArgumentException::class, $violation->getPrevious());
            self::assertStringContainsString('is not greater than', $violation->getMessage());
        }
    }

    #[DoesNotPerformAssertions]
    public function testEveryRepeatedContractHolds(): void
    {
        $this->account->between(9);
    }

    #[TestWith([11])]
    #[TestWith([1])]
    public function testEveryRepeatedContractIsChecked(int $amount): void
    {
        $this->expectException(PreconditionViolation::class);
        $this->account->between($amount);
    }

    #[DoesNotPerformAssertions]
    public function testDefaultValuesAreAvailable(): void
    {
        $this->account->withDefault(10);
    }

    public function testVariadicArgumentsAreCollected(): void
    {
        $this->account->variadic(1, 2, 3);

        $this->expectException(PreconditionViolation::class);
        $this->account->variadic(1, -2);
    }

    public function testEmptyVariadicArgumentsAreAnEmptyList(): void
    {
        $this->expectException(PreconditionViolation::class);
        $this->account->variadic();
    }

    public function testStaticMethodContractsUseClassScope(): void
    {
        Account::staticMethod(10);

        $this->expectException(PreconditionViolation::class);
        $this->expectExceptionMessage(Account::class . '::staticMethod(1)');
        Account::staticMethod(1);
    }

    public function testThisInStaticMethodIsAnInvalidContract(): void
    {
        $this->expectException(InvalidContractException::class);
        $this->expectExceptionMessage('uses $this, but the method is static');
        Account::staticWithThis(100);
    }

    public function testThisInStringOfStaticMethodIsValid(): void
    {
        Account::staticWithThisInString('account');

        $this->expectException(PreconditionViolation::class);
        Account::staticWithThisInString('$this');
    }

    public function testSyntaxErrorIsAnInvalidContract(): void
    {
        $this->expectException(InvalidContractException::class);
        $this->expectExceptionMessage('Contract "$amount >" of ' . Account::class . '::syntaxError() can not be compiled');
        $this->account->syntaxError(1);
    }

    public function testNonBooleanResultIsAnInvalidContract(): void
    {
        $this->expectException(InvalidContractException::class);
        $this->expectExceptionMessage('must return a boolean or null (for assertions), int returned');
        $this->account->notBoolean(1);
    }

    public function testErrorInsideExpressionIsReportedAsViolation(): void
    {
        try {
            $this->account->brokenExpression();
            self::fail('A violation was expected');
        } catch (PreconditionViolation $violation) {
            self::assertInstanceOf(Error::class, $violation->getPrevious());
        }
    }

    public function testTrailingCommentDoesNotBreakTheExpression(): void
    {
        $this->account->withComment(1);

        $this->expectException(PreconditionViolation::class);
        $this->account->withComment(0);
    }

    public function testContractCanCallPrivateMethods(): void
    {
        $this->account->guarded(2);

        $this->expectException(PreconditionViolation::class);
        $this->account->guarded(3);
    }

    public function testViolationPointsToTheMethodDeclaration(): void
    {
        try {
            $this->account->aboveLimit(1);
            self::fail('A violation was expected');
        } catch (ContractViolation $violation) {
            self::assertSame('aboveLimit', $violation->methodName);
            self::assertSame(Account::class, $violation->className);
            self::assertSame('$amount > $this->limit', $violation->contract);
            self::assertStringEndsWith('Fixtures/Verify/Account.php', $violation->getFile());
            self::assertSame(23, $violation->getLine());
        }
    }

    /**
     * @return iterable<string, array{object, int}>
     */
    public static function providerInheritance(): iterable
    {
        yield 'own contract of a subclass' => [new ChildWithoutInheritance(), 1];
        yield 'own contract with {@inheritdoc}' => [new ChildWithInheritDoc(), 1];
        yield 'parent contract with @inheritDoc' => [new ChildWithInheritDoc(), 2];
        yield 'grandparent contract' => [new ChildWithInheritDoc(), 3];
        yield 'own contract of an implementation' => [new RangeWithoutInheritance(), 1];
        yield 'first interface with {@inheritdoc}' => [new RangeWithInheritDoc(), 2];
        yield 'second interface with {@inheritdoc}' => [new RangeWithInheritDoc(), 3];
    }

    #[DataProvider('providerInheritance')]
    public function testInheritedPreconditionsAreChecked(object $subject, int $value): void
    {
        self::assertTrue(\method_exists($subject, 'add'));

        $this->expectException(PreconditionViolation::class);
        $subject->add($value);
    }

    /**
     * @return iterable<string, array{object, int}>
     */
    public static function providerNoInheritance(): iterable
    {
        yield 'parent contract is not inherited by default' => [new ChildWithoutInheritance(), 2];
        yield 'interface A contract is not inherited by default' => [new RangeWithoutInheritance(), 2];
        yield 'interface B contract is not inherited by default' => [new RangeWithoutInheritance(), 3];
        yield 'value valid for the whole hierarchy' => [new ChildWithInheritDoc(), 4];
    }

    #[DataProvider('providerNoInheritance')]
    public function testPreconditionsOfParentsAreOptIn(object $subject, int $value): void
    {
        self::assertTrue(\method_exists($subject, 'add'));
        $subject->add($value);
    }

    public function testParentContractIsEvaluatedInParentScope(): void
    {
        $child = new PrivateChild();
        $child->set(10);

        $this->expectException(PreconditionViolation::class);
        $this->expectExceptionMessage('$value >= $this->minimum');
        $child->set(9);
    }
}
