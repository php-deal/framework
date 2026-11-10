<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Functional;

use PhpDeal\Contract\BoundContract;
use PhpDeal\Contract\ContractFetcher;
use PhpDeal\Contract\ContractRewriter;
use PhpDeal\Tests\Fixtures\Ensure\InterfaceCounter;
use PhpDeal\Tests\Fixtures\Invariant\Particle;
use PhpDeal\Tests\Fixtures\Verify\Account;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

/**
 * On PHP 8.5+ the contracts of woven classes are compiled into closures, on the line of the attribute
 */
#[CoversClass(ContractRewriter::class)]
#[CoversClass(ContractFetcher::class)]
#[CoversClass(BoundContract::class)]
#[RequiresPhp('>= 8.5.0')]
final class CompiledContractTest extends TestCase
{
    private ContractFetcher $fetcher;

    protected function setUp(): void
    {
        $this->fetcher = new ContractFetcher();
    }

    public function testPreconditionIsCompiledOnTheLineOfTheAttribute(): void
    {
        [$contract] = $this->fetcher->getPreconditions(new ReflectionMethod(Account::class, 'aboveLimit'));

        self::assertNotNull($contract->check);
        $closure = new ReflectionFunction($contract->check);
        self::assertSame(self::findLine('Verify/Account.php', "#[Verify('\$amount > \$this->limit')]"), $closure->getStartLine());
        self::assertSame(Account::class, $closure->getClosureScopeClass()?->name);
    }

    public function testInterfaceContractRunsInTheScopeOfTheImplementingClass(): void
    {
        $contracts = $this->fetcher->getPostconditions(new ReflectionMethod(InterfaceCounter::class, 'add'));

        self::assertCount(3, $contracts);
        foreach ($contracts as $contract) {
            self::assertNotNull($contract->check);
            self::assertSame(InterfaceCounter::class, new ReflectionFunction($contract->check)->getClosureScopeClass()?->name);
        }
    }

    public function testInvariantIsCompiled(): void
    {
        [$contract] = $this->fetcher->getInvariants(new ReflectionClass(Particle::class));

        self::assertNotNull($contract->check);
        $particle = new Particle(10);
        self::assertTrue(($contract->check)($particle));
        new ReflectionProperty(Particle::class, 'speed')->setValue($particle, -10.0);
        self::assertFalse(($contract->check)($particle));
    }

    public function testThisInStaticMethodIsLeftToTheRunTimeCheck(): void
    {
        [$contract] = $this->fetcher->getPreconditions(new ReflectionMethod(Account::class, 'staticWithThis'));

        self::assertNull($contract->check);
    }

    /**
     * Returns the line of the code in a fixture source file
     */
    private static function findLine(string $fixture, string $code): int
    {
        $lines = \file(\dirname(__DIR__) . '/Fixtures/' . $fixture);
        self::assertIsArray($lines);
        foreach ($lines as $index => $line) {
            if (\str_contains($line, $code)) {
                return $index + 1;
            }
        }
        self::fail("{$code} not found in {$fixture}");
    }
}
