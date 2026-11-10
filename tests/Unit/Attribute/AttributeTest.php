<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Attribute;

use Attribute;
use PhpDeal\Attribute\Contract;
use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Inherit;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Verify::class)]
#[CoversClass(Ensure::class)]
#[CoversClass(Invariant::class)]
#[CoversClass(Inherit::class)]
final class AttributeTest extends TestCase
{
    /**
     * @param class-string $attributeClass
     */
    #[TestWith([Verify::class, Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE])]
    #[TestWith([Ensure::class, Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE])]
    #[TestWith([Invariant::class, Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE])]
    #[TestWith([Inherit::class, Attribute::TARGET_METHOD | Attribute::TARGET_CLASS])]
    public function testAttributeTargets(string $attributeClass, int $flags): void
    {
        $attributes = new ReflectionClass($attributeClass)->getAttributes(Attribute::class);

        self::assertCount(1, $attributes);
        self::assertSame($flags, $attributes[0]->newInstance()->flags);
    }

    /**
     * @return iterable<string, array{Contract}>
     */
    public static function providerContracts(): iterable
    {
        yield 'verify' => [new Verify('$a > 0')];
        yield 'ensure' => [new Ensure('$a > 0')];
        yield 'invariant' => [new Invariant('$a > 0')];
    }

    #[DataProvider('providerContracts')]
    public function testContractsExposeTheirExpression(Contract $contract): void
    {
        self::assertSame('$a > 0', $contract->expression);
    }
}
