<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Functional;

use PhpDeal\Exception\InvariantViolation;
use PhpDeal\Tests\Fixtures\Invariant\ChildValue;
use PhpDeal\Tests\Fixtures\Invariant\InterfaceValue;
use PhpDeal\Tests\Fixtures\Invariant\Particle;
use PhpDeal\Tests\Fixtures\Invariant\SelfCheckingParticle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(\PhpDeal\Aspect\InvariantCheckerAspect::class)]
final class InvariantTest extends TestCase
{
    public function testInvariantHolds(): void
    {
        $particle = new Particle();
        $particle->accelerate(10, 30);

        self::assertSame(300.0, $particle->getSpeed());
    }

    public function testInvariantIsCheckedAfterTheConstructor(): void
    {
        try {
            $speed = new Particle(-1)->getSpeed();
            self::fail('A violation was expected, but the speed is ' . $speed);
        } catch (InvariantViolation $violation) {
            self::assertStringEndsWith('->__construct(-1.0)', $violation->getMessage());
        }
    }

    public function testInvariantIsCheckedAfterEveryPublicMethod(): void
    {
        $this->expectException(InvariantViolation::class);
        new Particle()->accelerate(10, 3e7); // faster than light!
    }

    public function testInvariantIsCheckedAfterSeveralMethods(): void
    {
        $particle = new Particle();
        $particle->accelerate(10, 30);

        $this->expectException(InvariantViolation::class);
        $particle->decelerate(20, 20); // negative speed
    }

    public function testInvariantCanCallPublicMethodsOfItsClass(): void
    {
        $particle = new SelfCheckingParticle();
        $particle->setSpeed(10);
        self::assertSame(10.0, $particle->getSpeed());

        $this->expectException(InvariantViolation::class);
        $particle->setSpeed(-1);
    }

    public function testInheritedInvariantsHold(): void
    {
        $value = new ChildValue();
        $value->setValue(4);

        self::assertSame(4, $value->value);
    }

    #[TestWith([1], 'own invariant')]
    #[TestWith([2], 'parent invariant')]
    #[TestWith([3], 'grandparent invariant')]
    public function testInvariantsAreInheritedFromParents(int $value): void
    {
        $this->expectException(InvariantViolation::class);
        new ChildValue()->setValue($value);
    }

    #[TestWith([1], 'own invariant')]
    #[TestWith([2], 'invariant of the first interface')]
    #[TestWith([3], 'invariant of the second interface')]
    public function testInvariantsAreInheritedFromInterfaces(int $value): void
    {
        $this->expectException(InvariantViolation::class);
        new InterfaceValue()->setValue($value);
    }
}
