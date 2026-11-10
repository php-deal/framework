<?php

declare(strict_types=1);

namespace Example\Inventory;

use PhpDeal\Exception\PreconditionViolation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Tests derived from SPEC.md and from the contracts of {@see StockItem}
 *
 * The postconditions and invariants are checked by PhpDeal on every call, so each test that drives the object through
 * valid calls also verifies them. The tests add what the contracts can not prove on their own: the examples of the
 * specification, the boundaries of the preconditions and the business errors.
 */
final class StockItemTest extends TestCase
{
    public function testExampleOfTheSpecification(): void
    {
        $item = new StockItem('ABC-1234');
        $item->receive(10);
        $item->reserve(4);
        $item->ship(3);

        self::assertSame(7, $item->onHand());
        self::assertSame(1, $item->reserved());
        self::assertSame(6, $item->available());
    }

    /**
     * R1, and a canary: when this test fails, the contracts are not woven at all
     */
    #[TestWith(['abc-1234'])]
    #[TestWith(['ABC1234'])]
    #[TestWith(['ABCD-123'])]
    #[TestWith([''])]
    public function testSkuMustMatchThePattern(string $sku): void
    {
        $this->expectException(PreconditionViolation::class);

        $item = new StockItem($sku);
        self::fail('The SKU was accepted: ' . $item->sku);
    }

    /**
     * R6: the boundary of the precondition, exactly the available quantity is accepted
     */
    public function testAvailableQuantityCanBeReserved(): void
    {
        $item = self::itemWith(onHand: 5, reserved: 2);
        $item->reserve(3);

        self::assertSame(0, $item->available());
    }

    /**
     * @param callable(StockItem): void $call
     */
    #[DataProvider('callsBreakingPreconditions')]
    public function testCallerMustMeetThePreconditions(callable $call): void
    {
        $item = self::itemWith(onHand: 5, reserved: 2);

        try {
            $call($item);
            self::fail('A precondition violation was expected');
        } catch (PreconditionViolation) {
            // The body did not run, so the state is unchanged
            self::assertSame(5, $item->onHand());
            self::assertSame(2, $item->reserved());
        }
    }

    /**
     * Calls just outside the boundaries of each precondition, with onHand 5 and reserved 2
     *
     * @return iterable<string, array{callable(StockItem): void}>
     */
    public static function callsBreakingPreconditions(): iterable
    {
        yield 'R5: receive nothing' => [static fn(StockItem $item) => $item->receive(0)];
        yield 'R5: receive a negative amount' => [static fn(StockItem $item) => $item->receive(-1)];
        yield 'R6: reserve nothing' => [static fn(StockItem $item) => $item->reserve(0)];
        yield 'R6: reserve more than is free' => [static fn(StockItem $item) => $item->reserve(4)];
        yield 'R7: release more than reserved' => [static fn(StockItem $item) => $item->release(3)];
        yield 'R8: ship more than reserved' => [static fn(StockItem $item) => $item->ship(3)];
        yield 'R8: ship nothing' => [static fn(StockItem $item) => $item->ship(0)];
        yield 'R9: negative count' => [static fn(StockItem $item) => $item->recordCount(-1)];
    }

    /**
     * R9: a business error, reported to the user, not a contract violation
     */
    public function testCountBelowTheReservationsIsRejected(): void
    {
        $item = self::itemWith(onHand: 5, reserved: 2);

        try {
            $item->recordCount(1);
            self::fail('The count was expected to be rejected');
        } catch (CountBelowReserved $error) {
            self::assertSame('Count is below the reserved quantity', $error->getMessage());
            self::assertSame(5, $item->onHand());
        }
    }

    #[TestWith([2])]
    #[TestWith([9])]
    public function testCountReplacesTheQuantityOnHand(int $counted): void
    {
        $item = self::itemWith(onHand: 5, reserved: 2);
        $item->recordCount($counted);

        self::assertSame($counted, $item->onHand());
        self::assertSame(2, $item->reserved());
    }

    /**
     * Random sequences of valid calls: inputs are sampled from the preconditions, and the postconditions and
     * invariants check every step. A seeded engine keeps a failure reproducible.
     */
    #[DataProvider('seeds')]
    public function testRandomValidOperationsKeepEveryContract(int $seed): void
    {
        $random = new Randomizer(new Mt19937($seed));
        $item   = new StockItem('XYZ-0001');
        for ($step = 0; $step < 200; ++$step) {
            $operation = $random->getInt(0, 4);
            if ($operation === 0) {
                $item->receive($random->getInt(1, 50));
            } elseif ($operation === 1 && $item->available() > 0) {
                $item->reserve($random->getInt(1, $item->available()));
            } elseif ($operation === 2 && $item->reserved() > 0) {
                $item->release($random->getInt(1, $item->reserved()));
            } elseif ($operation === 3 && $item->reserved() > 0) {
                $item->ship($random->getInt(1, $item->reserved()));
            } elseif ($operation === 4) {
                $item->recordCount($random->getInt($item->reserved(), $item->onHand() + 10));
            }
        }

        self::assertSame($item->onHand() - $item->reserved(), $item->available());
    }

    /**
     * @return iterable<array{int}>
     */
    public static function seeds(): iterable
    {
        foreach ([1, 2, 3, 42, 2026] as $seed) {
            yield "seed {$seed}" => [$seed];
        }
    }

    private static function itemWith(int $onHand, int $reserved): StockItem
    {
        $item = new StockItem('ABC-1234');
        $item->receive($onHand);
        $item->reserve($reserved);

        return $item;
    }
}
