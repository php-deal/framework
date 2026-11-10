<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Contract;

use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;
use PhpDeal\Contract\BoundContract;
use PhpDeal\Contract\ContractFetcher;
use PhpDeal\Tests\Unit\Stub\Polygon;
use PhpDeal\Tests\Unit\Stub\Square;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(ContractFetcher::class)]
#[CoversClass(BoundContract::class)]
final class ContractFetcherTest extends TestCase
{
    private ContractFetcher $fetcher;

    protected function setUp(): void
    {
        $this->fetcher = new ContractFetcher();
    }

    public function testPreconditionsOfParentsAreNotInheritedByDefault(): void
    {
        $contracts = $this->fetcher->getPreconditions(new ReflectionMethod(Square::class, 'scale'));

        self::assertSame([['$factor !== 13.0', Square::class]], self::describe($contracts));
        self::assertContainsOnlyInstancesOf(Verify::class, \array_map(static fn(BoundContract $c) => $c->contract, $contracts));
    }

    public function testInheritAttributeEnablesParentPreconditions(): void
    {
        $contracts = $this->fetcher->getPreconditions(new ReflectionMethod(Square::class, 'resize'));

        self::assertSame([['$factor > 0', Polygon::class]], self::describe($contracts));
    }

    public function testInheritDocTagEnablesParentPreconditions(): void
    {
        $contracts = $this->fetcher->getPreconditions(new ReflectionMethod(Square::class, 'documented'));

        self::assertSame([['true', Square::class]], self::describe($contracts));
    }

    public function testPostconditionsIncludeParentsAndInterfacesInTheirScopes(): void
    {
        $contracts = $this->fetcher->getPostconditions(new ReflectionMethod(Square::class, 'scale'));

        self::assertSame(
            [
                ['$__result >= $this->size', Square::class],
                // Polygon and Shape declare the same expression, it is checked once in the closest scope
                ['$__result > 0', Polygon::class],
            ],
            self::describe($contracts),
        );
        self::assertContainsOnlyInstancesOf(Ensure::class, \array_map(static fn(BoundContract $c) => $c->contract, $contracts));
    }

    public function testPrivateParentMethodsAreNotPrototypes(): void
    {
        $contracts = $this->fetcher->getPostconditions(new ReflectionMethod(Square::class, 'hidden'));

        self::assertSame([], $contracts);
    }

    public function testInvariantsOfInterfacesUseTheScopeOfTheClass(): void
    {
        $contracts = $this->fetcher->getInvariants(new ReflectionClass(Square::class));

        self::assertSame(
            [
                ['$this->sides === 4', Square::class],
                ['$this->sides >= 3', Polygon::class],
                ['$this->sides > 0', Square::class],
            ],
            self::describe($contracts),
        );
        self::assertContainsOnlyInstancesOf(Invariant::class, \array_map(static fn(BoundContract $c) => $c->contract, $contracts));
    }

    public function testResultsAreCached(): void
    {
        $method = new ReflectionMethod(Square::class, 'scale');
        $class  = new ReflectionClass(Square::class);

        self::assertSame($this->fetcher->getPostconditions($method), $this->fetcher->getPostconditions($method));
        self::assertSame($this->fetcher->getInvariants($class), $this->fetcher->getInvariants($class));
    }

    /**
     * @param list<BoundContract> $contracts
     *
     * @return list<array{string, string}>
     */
    private static function describe(array $contracts): array
    {
        return \array_map(
            static fn(BoundContract $contract): array => [$contract->contract->expression, $contract->scope],
            $contracts,
        );
    }
}
