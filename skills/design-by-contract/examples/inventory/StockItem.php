<?php

declare(strict_types=1);

namespace Example\Inventory;

use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;

/**
 * Stock of one product in the warehouse, see SPEC.md for the rules R1-R9
 */
#[Invariant('$this->onHand >= 0')]                                         // R2
#[Invariant('$this->reserved >= 0 && $this->reserved <= $this->onHand')] // R3
class StockItem
{
    public const string SKU_PATTERN = '/^[A-Z]{3}-\d{4}$/';

    private int $onHand = 0;

    private int $reserved = 0;

    #[Verify('\preg_match(self::SKU_PATTERN, $sku) === 1')] // R1
    public function __construct(public readonly string $sku) {}

    public function onHand(): int
    {
        return $this->onHand;
    }

    public function reserved(): int
    {
        return $this->reserved;
    }

    /**
     * Quantity that can still be reserved (R4)
     */
    #[Ensure('$__result === $this->onHand - $this->reserved')]
    public function available(): int
    {
        return $this->onHand - $this->reserved;
    }

    /**
     * Adds received goods to the stock on hand (R5)
     */
    #[Verify('$quantity > 0')]
    #[Ensure('$this->onHand === $__old->onHand + $quantity')]
    #[Ensure('$this->reserved === $__old->reserved')]
    public function receive(int $quantity): void
    {
        $this->onHand += $quantity;
    }

    /**
     * Reserves goods for an order; callers check {@see available()} first (R6)
     */
    #[Verify('$quantity > 0')]
    #[Verify('$quantity <= $this->available()')]
    #[Ensure('$this->reserved === $__old->reserved + $quantity')]
    #[Ensure('$this->onHand === $__old->onHand')]
    public function reserve(int $quantity): void
    {
        $this->reserved += $quantity;
    }

    /**
     * Releases the reservation of a cancelled order (R7)
     */
    #[Verify('$quantity > 0 && $quantity <= $this->reserved')]
    #[Ensure('$this->reserved === $__old->reserved - $quantity')]
    #[Ensure('$this->onHand === $__old->onHand')]
    public function release(int $quantity): void
    {
        $this->reserved -= $quantity;
    }

    /**
     * Ships reserved goods out of the warehouse (R8)
     */
    #[Verify('$quantity > 0 && $quantity <= $this->reserved')]
    #[Ensure('$this->onHand === $__old->onHand - $quantity')]
    #[Ensure('$this->reserved === $__old->reserved - $quantity')]
    public function ship(int $quantity): void
    {
        $this->onHand -= $quantity;
        $this->reserved -= $quantity;
    }

    /**
     * Replaces the quantity on hand with a stock count entered by staff (R9)
     *
     * The count comes from a person, so a count below the reservations is an expected business case: it is handled
     * in code, not with a precondition.
     *
     * @throws CountBelowReserved When the count is lower than the reserved quantity
     */
    #[Verify('$counted >= 0')]
    #[Ensure('$this->onHand === $counted')]
    #[Ensure('$this->reserved === $__old->reserved')]
    public function recordCount(int $counted): void
    {
        if ($counted < $this->reserved) {
            throw new CountBelowReserved('Count is below the reserved quantity');
        }
        $this->onHand = $counted;
    }
}
