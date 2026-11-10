# Tests derived from contracts

In a woven class every call checks the contracts. That changes what a test has to do:

- **Postconditions and invariants need no assertions of their own.** Each valid call checks them, and a broken one
  throws. A test only has to make many different valid calls.
- **Preconditions tell you how to choose inputs.** The `#[Verify]` expressions describe the valid input space, so you
  can read off the boundaries and sample valid arguments from them.
- **The specification stays the oracle.** Contracts and code are both written from your reading of the
  specification, so they can share the same mistake. The examples of the specification, with explicit expected
  values, catch it.

The [worked example](../examples/inventory/StockItemTest.php) shows each kind of test below.

## Contents

- [Bootstrap](#bootstrap)
- [1. Specification examples](#1-specification-examples)
- [2. Precondition boundaries](#2-precondition-boundaries)
- [3. Business errors](#3-business-errors)
- [4. Random valid sequences](#4-random-valid-sequences)
- [When a test reports a violation](#when-a-test-reports-a-violation)
- [Contracts that the tests do not reach](#contracts-that-the-tests-do-not-reach)

## Bootstrap

The test run must weave the classes, otherwise every contract is silently skipped:

```php
// tests/bootstrap.php
use PhpDeal\ContractApplication;

require \dirname(__DIR__) . '/vendor/autoload.php';

ContractApplication::getInstance()->init([
    'debug'        => true,
    'appDir'       => \dirname(__DIR__),
    'cacheDir'     => \dirname(__DIR__) . '/var/cache/contracts-test',
    'includePaths' => [\dirname(__DIR__) . '/src'],
]);
```

Do not `require` the class files directly in tests: only classes loaded through the Composer autoloader are woven.

## 1. Specification examples

Turn each example or acceptance criterion into a test with explicit expected values, and name the test after the
rule or the example:

```php
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
```

When the specification has no examples, write one per rule from your rule table and tell the user that they are
your own examples.

## 2. Precondition boundaries

For each `#[Verify]`, find the boundary values and test both sides:

| Precondition                        | Inside the boundary (passes) | Outside the boundary (violation) |
|-------------------------------------|------------------------------|----------------------------------|
| `$quantity > 0`                     | `1`                          | `0`, `-1`                        |
| `$quantity <= $this->available()`   | `available()`                | `available() + 1`                |
| `$percent >= 1 && $percent <= 100`  | `1`, `100`                   | `0`, `101`                       |
| `\preg_match(self::SKU, $sku) === 1`| `'ABC-1234'`                 | `''`, `'abc-1234'`, `'ABC1234'`  |
| nullable and positive              | `null`, `1`                  | `0`                              |
| enum in an allowed list             | each allowed case            | each other case                  |

The violating calls fit in one data provider. Also check that the state did not change, since preconditions run
before the method body:

```php
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
        self::assertSame(5, $item->onHand());
        self::assertSame(2, $item->reserved());
    }
}

/**
 * @return iterable<string, array{callable(StockItem): void}>
 */
public static function callsBreakingPreconditions(): iterable
{
    yield 'R6: reserve more than is available' => [static fn(StockItem $item) => $item->reserve(4)];
    yield 'R8: ship more than is reserved'     => [static fn(StockItem $item) => $item->ship(3)];
}
```

A failing precondition test is also the first thing to check when contracts seem to do nothing: if no violation
is thrown, the class is not woven (see [setup.md](setup.md)).

## 3. Business errors

Rules that the code handles itself, such as rejected input or a user-visible message, get ordinary tests. Assert the
exception, its message and the unchanged state:

```php
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
```

## 4. Random valid sequences

This is contract-based testing: generate valid calls from the preconditions and let the postconditions and
invariants judge the outcome. No extra library is needed. A seeded `Random\Randomizer` makes every failure
reproducible:

```php
#[DataProvider('seeds')]
public function testRandomValidOperationsKeepEveryContract(int $seed): void
{
    $random = new Randomizer(new Mt19937($seed));
    $item   = new StockItem('XYZ-0001');
    for ($step = 0; $step < 200; ++$step) {
        $operation = $random->getInt(0, 3);
        if ($operation === 0) {
            $item->receive($random->getInt(1, 50));                          // Verify: $quantity > 0
        } elseif ($operation === 1 && $item->available() > 0) {
            $item->reserve($random->getInt(1, $item->available()));          // Verify: <= available()
        } elseif ($operation === 2 && $item->reserved() > 0) {
            $item->ship($random->getInt(1, $item->reserved()));              // Verify: <= reserved
        } elseif ($operation === 3) {
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
```

How to derive the generator from the contracts:

- Each command method is one operation. Skip an operation when no valid argument exists, for example nothing to
  ship.
- Turn each precondition into a range for `getInt()`, or into a choice for `pickArrayKeys()` and `shuffleArray()`.
  `$a > 0 && $a <= $this->x()` becomes `getInt(1, $this->x())`.
- Bias the ranges towards the boundaries: include the minimum, the maximum and values next to them.
- For strings that match a pattern, build them from parts, for example
  `$random->getBytesFromString('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 3) . '-' . $random->getBytesFromString('0123456789', 4)`.
- End with one plain assertion so that PHPUnit does not report the test as risky.

When the project already uses a property-based testing library such as [Eris](https://github.com/giorgiosironi/eris),
generate the inputs with it instead. The contracts stay the same.

## When a test reports a violation

The exception names the contract, the class, the method and the arguments. Its file and line point to the method
that declares the contract:

```
PhpDeal\Exception\PostconditionViolation: Postcondition "$this->onHand === $__old->onHand - $quantity" violated
in Example\Inventory\StockItem->ship(3)
```

Then decide which part is wrong:

- **The code** breaks a rule of the specification: fix the code.
- **The contract** is stronger than the specification, or reads it wrongly: fix the contract, and record why.
- **The test** calls the method outside its preconditions: fix the generator.

Do not delete or weaken a contract only to get a green run.

## Contracts that the tests do not reach

After the tests pass, compare the rule table with the tests. Each `#[Ensure]` should run in at least one test, and so
should each method of a class with an `#[Invariant]`. A contract that never runs proves nothing.
