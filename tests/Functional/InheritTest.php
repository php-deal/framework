<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Functional;

use PhpDeal\Exception\InvariantViolation;
use PhpDeal\Exception\PostconditionViolation;
use PhpDeal\Exception\PreconditionViolation;
use PhpDeal\Tests\Fixtures\Inherit\BuggyWallet;
use PhpDeal\Tests\Fixtures\Inherit\WalletWithClassInheritance;
use PhpDeal\Tests\Fixtures\Inherit\WalletWithMethodInheritance;
use PhpDeal\Tests\Fixtures\Inherit\WalletWithoutInheritance;
use PHPUnit\Framework\TestCase;

final class InheritTest extends TestCase
{
    public function testContractsOfInterfacesAreIgnoredWithoutInherit(): void
    {
        $wallet = new WalletWithoutInheritance();
        $wallet->add(-10);

        self::assertSame(-10, $wallet->getAmount());
    }

    public function testMethodInheritsContracts(): void
    {
        $wallet = new WalletWithMethodInheritance();
        $wallet->add(10);

        self::assertSame(10, $wallet->getAmount());
    }

    public function testInheritedPreconditionIsCheckedBeforeTheBody(): void
    {
        $wallet = new WalletWithMethodInheritance();
        try {
            $wallet->add(0);
            self::fail('A violation was expected');
        } catch (PreconditionViolation) {
            self::assertSame(0, $wallet->bodyCalls, 'The method body must not run');
        }
    }

    public function testInheritedPostconditionIsChecked(): void
    {
        $this->expectException(PostconditionViolation::class);
        new BuggyWallet()->add(5);
    }

    public function testMethodInheritanceDoesNotEnableInvariants(): void
    {
        $wallet = new WalletWithMethodInheritance();
        $wallet->forceAmount(-1);

        self::assertSame(-1, $wallet->getAmount());
    }

    public function testClassInheritsContractsOfEveryMethod(): void
    {
        $wallet = new WalletWithClassInheritance();
        $wallet->add(5);
        self::assertSame(5, $wallet->getAmount());

        $this->expectException(PreconditionViolation::class);
        $wallet->add(-5);
    }

    public function testClassInheritsInvariants(): void
    {
        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('Invariant "$this->getAmount() >= 0"');
        new WalletWithClassInheritance()->forceAmount(-1);
    }
}
