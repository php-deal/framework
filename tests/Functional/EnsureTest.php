<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Functional;

use PhpDeal\Exception\PostconditionViolation;
use PhpDeal\Tests\Fixtures\Ensure\ChildCounter;
use PhpDeal\Tests\Fixtures\Ensure\Counter;
use PhpDeal\Tests\Fixtures\Ensure\InterfaceCounter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(\PhpDeal\Aspect\PostconditionCheckerAspect::class)]
final class EnsureTest extends TestCase
{
    private Counter $counter;

    protected function setUp(): void
    {
        $this->counter = new Counter();
    }

    public function testPostconditionsHold(): void
    {
        $this->counter->increment(50);

        self::assertSame(92.0, $this->counter->getValue());
    }

    public function testEveryPostconditionIsChecked(): void
    {
        $this->expectException(PostconditionViolation::class);
        $this->expectExceptionMessage('Postcondition "$this->value > 0" violated');
        $this->counter->increment(-50);
    }

    public function testOldStateIsAvailable(): void
    {
        $this->expectException(PostconditionViolation::class);
        $this->expectExceptionMessage('$this->value === $__old->value + $amount');
        $this->counter->badIncrement(40);
    }

    public function testResultIsAvailable(): void
    {
        $this->expectException(PostconditionViolation::class);
        $this->expectExceptionMessage('$__result === $this->value');
        $this->counter->cheatingGetter();
    }

    public function testObjectIsClonedOnlyWhenOldStateIsUsed(): void
    {
        $this->counter->withoutOldState();
        self::assertSame(0, $this->counter->clones);

        $this->counter->increment(1);
        self::assertSame(0, $this->counter->clones, 'The clone is taken, the original is not changed');
    }

    public function testStaticMethodsAreChecked(): void
    {
        self::assertSame('value', Counter::staticFactory());
    }

    #[TestWith([50])]
    public function testPostconditionsOfTheHierarchyHold(int $value): void
    {
        $counter = new ChildCounter();
        $counter->add($value);

        $this->addToAssertionCount(1);
    }

    #[TestWith([1], 'own contract')]
    #[TestWith([2], 'parent contract')]
    #[TestWith([-1], 'grandparent contract')]
    public function testPostconditionsAreInheritedFromParents(int $value): void
    {
        $this->expectException(PostconditionViolation::class);
        new ChildCounter()->add($value);
    }

    #[TestWith([-1], 'contract of the first interface')]
    #[TestWith([-2], 'contract of the second interface')]
    public function testPostconditionsAreInheritedFromInterfaces(int $value): void
    {
        $this->expectException(PostconditionViolation::class);
        new InterfaceCounter()->add($value);
    }
}
