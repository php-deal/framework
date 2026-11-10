# From business rules to contracts

## Contents

- [Reading a specification](#reading-a-specification)
- [Contract or code?](#contract-or-code)
- [Rule patterns](#rule-patterns)
- [State machines](#state-machines)
- [Aggregates and collections](#aggregates-and-collections)
- [Rule table](#rule-table)

## Reading a specification

Go through the text sentence by sentence and mark:

- **Nouns** that have state: they become classes or value objects (`Order`, `StockItem`, `Money`).
- **Verbs** that change state: they become command methods (`reserve()`, `ship()`, `cancel()`).
- **Questions** that callers ask: they become query methods (`available()`, `canCancel()`, `total()`).
- **Constraints** ("must", "only", "never", "at most", "always", "unless", "until"): they become contracts or code.
- **Examples** ("given 10 items, after reserving 4..."): they become tests.

Keep one rule per line, with an ID and the original words. Split compound sentences: "Shipping removes the goods
from stock and from the reservations" is two postconditions.

## Contract or code?

| Question                                                                 | If yes                                        |
|--------------------------------------------------------------------------|-----------------------------------------------|
| Does the rule describe a reaction that users or other systems see (a message, a status, a refund)? | Code with a domain exception or a result type |
| Can the input come straight from a person, a file or a remote system?    | Validate in code, at the boundary             |
| Must the rule still hold in production when the contract kernel is off?  | Code (and an `Ensure` documenting the result) |
| Is it an obligation of the calling code, which can check it first?       | `#[Verify]`                                   |
| Is it a promise about the effect or the result of a method?              | `#[Ensure]`                                   |
| Is it true of the object whenever no method is running?                  | `#[Invariant]`                                |
| Is it about time, order of events in other systems, performance, security or persistence? | Not a contract                |

Contracts and code work together. The code handles the expected business cases. The contracts state what must then
be true, and catch the cases that "can not happen":

```php
/**
 * @throws CountBelowReserved R9: counts come from staff, a wrong count is an expected case
 */
#[Verify('$counted >= 0')]                            // R9: the form only accepts whole numbers >= 0
#[Ensure('$this->onHand === $counted')]               // R9: the count replaces the stock on hand
#[Ensure('$this->reserved === $__old->reserved')]     // frame condition: reservations are untouched
public function recordCount(int $counted): void
{
    if ($counted < $this->reserved) {
        throw new CountBelowReserved('Count is below the reserved quantity');
    }
    $this->onHand = $counted;
}
```

## Rule patterns

| Specification wording                                   | Contract                                                                  |
|---------------------------------------------------------|---------------------------------------------------------------------------|
| "The amount is positive"                                | `#[Verify('$amount > 0')]`                                                |
| "Between 1 and 100 percent"                             | `#[Verify('$percent >= 1 && $percent <= 100')]`                           |
| "One of EUR, USD"                                       | `#[Verify('\in_array($currency, self::CURRENCIES, true)')]`               |
| "A name is not blank and at most 80 characters"         | `#[Verify('\trim($name) !== \'\' && \mb_strlen($name) <= 80')]`           |
| "The code looks like ABC-1234"                          | `#[Verify('\preg_match(self::CODE_PATTERN, $code) === 1')]`               |
| "Only when enough is available"                         | `#[Verify('$quantity <= $this->available()')]`                            |
| "Only while the order is open"                          | `#[Verify('$this->status === \App\Order\Status::Open')]`                  |
| "The end is after the start"                            | `#[Verify('$end > $start')]`                                              |
| "Adds the amount to the balance"                        | `#[Ensure('$this->balance === $__old->balance + $amount')]`               |
| "Does not change the items"                             | `#[Ensure('$this->items === $__old->items')]`                             |
| "Returns a non-negative price"                          | `#[Ensure('$__result >= 0')]`                                             |
| "The discount never exceeds 30% of the total"           | `#[Ensure('$__result <= \intdiv($total * 30, 100)')]`                     |
| "Returns the order, now paid"                           | `#[Ensure('$__result->isPaid()')]`                                        |
| "The balance is never negative"                         | `#[Invariant('$this->balance >= 0')]`                                     |
| "Reserved never exceeds the stock"                      | `#[Invariant('$this->reserved <= $this->onHand')]`                        |
| "Order numbers are unique within a batch"               | `#[Invariant('\count(\array_unique($this->numbers)) === \count($this->numbers)')]` |

Longer rules, written as `!A || B` for "if A then B" and with closures for sums:

```php
// "A shipped order always has a tracking number"
#[Invariant('$this->status !== \App\Order\Status::Shipped || $this->tracking !== null')]
// "The total equals the sum of the lines"
#[Invariant('$this->total === \array_sum(\array_map(static fn(\App\Order\Line $line): int => $line->amount, $this->lines))')]
```

When a rule needs more than one line of logic, move it into a private method with a name from the business
vocabulary: `#[Invariant('$this->linesAddUpToTotal()')]`. The PHPStan extension of PhpDeal then sees that the method
is used.

## State machines

Specifications often describe a lifecycle: "an order is created as draft, can be placed, then paid or cancelled;
a paid order can be shipped". Model the states as an enum and give each transition:

- a precondition listing the allowed source states;
- a postcondition naming the target state;
- invariants for the data that each state requires.

```php
use PhpDeal\Attribute\{Ensure, Invariant, Verify};

#[Invariant('$this->status !== \App\Order\Status::Paid || $this->paidAt !== null')]
final class Order
{
    private \App\Order\Status $status = \App\Order\Status::Draft;
    private ?\DateTimeImmutable $paidAt = null;

    #[Verify('$this->status === \App\Order\Status::Placed')]
    #[Ensure('$this->status === \App\Order\Status::Paid')]
    #[Ensure('$this->paidAt === $at')]
    public function markPaid(\DateTimeImmutable $at): void
    {
        $this->status = \App\Order\Status::Paid;
        $this->paidAt = $at;
    }

    #[Verify('\in_array($this->status, [\App\Order\Status::Draft, \App\Order\Status::Placed], true)')]
    #[Ensure('$this->status === \App\Order\Status::Cancelled')]
    public function cancel(): void
    {
        $this->status = \App\Order\Status::Cancelled;
    }

    #[Ensure('$__result === \in_array($this->status, [\App\Order\Status::Draft, \App\Order\Status::Placed], true)')]
    public function canCancel(): bool
    {
        return \in_array($this->status, [\App\Order\Status::Draft, \App\Order\Status::Placed], true);
    }
}
```

When users trigger the transition directly ("the Cancel button"), the controller asks `canCancel()` and shows a
message. The precondition then only catches code that forgets to ask.

## Aggregates and collections

- Put rules that span several objects on the object that owns them (the aggregate root): "the lines of an order
  add up to its total" is an invariant of `Order`, not of `Line`.
- Value objects (`Money`, `Quantity`, `EmailAddress`) state their rules as constructor preconditions and invariants.
  Most other contracts then become shorter, because a `Money` is always valid.
- A method that adds to a collection usually has three postconditions: the count grew by one, the new item is
  there, and the old items are unchanged.

```php
#[Ensure('\count($this->lines) === \count($__old->lines) + 1')]
#[Ensure('$this->lines[\array_key_last($this->lines)] === $line')]
#[Ensure('\array_slice($this->lines, 0, -1) === $__old->lines')]
public function addLine(\App\Order\Line $line): void
{
    $this->lines[] = $line;
}
```

Here `===` works on `$__old->lines` because the array is copied by the clone, and the `Line` objects are compared
by identity.

## Rule table

Keep a table while you work, and give it to the user at the end. The [worked example](../examples/inventory) results
in:

| Rule | Contract or code                                                    | Where                          | Test                                          |
|------|---------------------------------------------------------------------|--------------------------------|-----------------------------------------------|
| R1   | `Verify('\preg_match(self::SKU_PATTERN, $sku) === 1')`              | `StockItem::__construct`       | `testSkuMustMatchThePattern`                  |
| R2   | `Invariant('$this->onHand >= 0')`                                   | `StockItem`                    | random sequences                              |
| R3   | `Invariant('$this->reserved >= 0 && $this->reserved <= $this->onHand')` | `StockItem`                | random sequences                              |
| R4   | `Ensure('$__result === $this->onHand - $this->reserved')`           | `StockItem::available`         | `testExampleOfTheSpecification`               |
| R5   | `Verify('$quantity > 0')`, two `Ensure`                              | `StockItem::receive`           | boundaries, random sequences                  |
| R6   | two `Verify`, two `Ensure`                                          | `StockItem::reserve`           | `testAvailableQuantityCanBeReserved`, boundaries |
| R7   | `Verify`, two `Ensure`                                              | `StockItem::release`           | boundaries, random sequences                  |
| R8   | `Verify`, two `Ensure`                                              | `StockItem::ship`              | example, boundaries                           |
| R9   | code: `CountBelowReserved`; `Verify('$counted >= 0')`, two `Ensure`  | `StockItem::recordCount`       | `testCountBelowTheReservationsIsRejected`     |
