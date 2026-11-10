# Contract expressions

A contract is one PHP expression in a string. PhpDeal compiles it into a closure that runs in the scope of the class
that declares the contract. On PHP 8.5+ the closure is written into the woven class (the `check:` argument of the
attribute), on PHP 8.4 it is compiled with `eval()` at run time. Never write `check:` yourself.

## Contents

- [Attributes](#attributes)
- [Variables](#variables)
- [Scope and names](#scope-and-names)
- [Results and errors](#results-and-errors)
- [Inheritance](#inheritance)
- [Pitfalls](#pitfalls)

## Attributes

| Attribute      | Target           | Checked                                        | Repeatable |
|----------------|------------------|------------------------------------------------|------------|
| `#[Verify]`    | method           | before the method body                         | yes        |
| `#[Ensure]`    | method           | after the method returns (not when it throws)  | yes        |
| `#[Invariant]` | class, interface | after the constructor and every public method  | yes        |
| `#[Inherit]`   | method, class    | enables the contracts of parents               | no         |

Import them with `use PhpDeal\Attribute\{Ensure, Inherit, Invariant, Verify};`. Prefer one attribute per rule
to one long `&&` chain: the violation message then names the exact rule that broke.

## Variables

| Variable            | Available in               | Value                                                        |
|---------------------|----------------------------|--------------------------------------------------------------|
| `$paramName`        | `Verify`, `Ensure`         | the argument, or its default value; a variadic is a list     |
| `$this`             | all, not in static methods | the object                                                   |
| `$__result`         | `Ensure`                   | the return value (`null` for `void`)                         |
| `$__old`            | `Ensure`                   | a shallow clone of the object taken before the call          |

An invariant can technically see the arguments and the result of whichever public method ran last. Do not use them
there: an invariant describes the object alone.

## Scope and names

- The expression can read private and protected members of the declaring class: `$this->balance`,
  `$this->isValid($x)`, `self::LIMIT`, `static::$registry`. A contract of an interface runs in the scope of the
  implementing class. A contract of a parent class runs in the scope of the parent and can read its private state.
- The expression is written for the global namespace. `use` imports do **not** apply:
  - classes, enums and interfaces are fully qualified: `$status === \App\Order\Status::Paid`,
    `$at instanceof \DateTimeImmutable`;
  - functions get a leading backslash: `\count()`, `\in_array($x, self::ALLOWED, true)`, `\preg_match(...)`;
  - `self`, `static` and `parent` resolve as usual.
- Strings inside the expression: use double quotes when they hold no `$`, as in `'$currency === "EUR"'`. Escape a
  single quote as `\'`. Put regular expressions and lists in class constants: `'\preg_match(self::SKU, $sku) === 1'`.
- A trailing `// comment` inside the string is allowed.

## Results and errors

| The expression...                                   | Outcome                                                        |
|-----------------------------------------------------|----------------------------------------------------------------|
| returns `true`                                      | the contract holds                                             |
| returns `null`                                      | the contract holds (for assertion helpers that return nothing) |
| returns `false`                                     | `PreconditionViolation` / `PostconditionViolation` / `InvariantViolation` |
| throws any `Throwable`                              | the violation, with the error as `getPrevious()`               |
| returns anything else (`1`, `'yes'`, an object)     | `InvalidContractException`: the contract itself is broken      |
| has a syntax error, or uses `$this` in a static method | `InvalidContractException`                                  |

Assertion libraries work, because failures are exceptions:
`#[Verify('\Assert\Assertion::email($email)')]`.

While an expression runs, all contract checks are suspended. An invariant can therefore call public methods of its
own class, `#[Invariant('$this->getBalance() >= 0')]`, without recursion.

## Inheritance

| Contract    | Taken from parent classes and interfaces                                               |
|-------------|-----------------------------------------------------------------------------------------|
| `Ensure`    | always, when the method declares a postcondition itself or uses `#[Inherit]`            |
| `Invariant` | always, when the class declares an invariant itself or uses `#[Inherit]`                |
| `Verify`    | only with `#[Inherit]` on the method or the class, or an `{@inheritdoc}` tag             |

A class without any contract attribute of its own and without `#[Inherit]` is not woven, even when its interface has
contracts. Put `#[Inherit]` on every implementation of a contracted interface. Preconditions are opt-in because a
subclass may accept more than its parent (Liskov: require no more, promise no less).

## Pitfalls

**Floats.** `0.1 + 0.2 === 0.3` is false. Keep money in integer minor units (cents), or compare with a tolerance:
`'\abs($this->total - ($__old->total + $amount)) < 1e-9'`.

**Shallow `$__old`.** `$__old` is `clone $this`. Objects inside it are the same instances as in `$this`, so
`$__old->items->count()` already sees the change. Either store immutable values (arrays, scalars, value objects), or
deep-copy in `__clone()`:

```php
public function __clone()
{
    $this->lines = clone $this->lines;
}
```

The object is cloned only when an expression of the method contains `$__old`. When the class can not be cloned (a
private `__clone()`, for example), `$__old` is undefined.

**Arrays.** `===` on arrays compares keys, values, types and order, while `==` ignores order and types. Pick the one
the rule means. For "nothing else changed" use `'$this->lines === $__old->lines'`.

**Variadics.** `int ...$amounts` arrives as a list. `$amounts > 0` compares an array with a number and is always
true. Check the items: `'$amounts !== [] && \min($amounts) > 0'`.

**Constructors.** `#[Verify]` and `#[Ensure]` work on constructors. There `$__result` is always `null`, and `$__old`
is a clone of the object before its properties are set, so reading a typed property from it fails. Check `$this`.

**Nullables.** Guard first: `'$discount === null || ($discount > 0 && $discount <= 100)'`.

**Implications.** "If shipped, it has a tracking number" is `'$this->status !== \App\Status::Shipped || $this->tracking !== null'`.

**Dates.** `DateTimeImmutable` objects compare with `<`, `<=` and `==`. Pass the clock in as an argument or a
property. Do not call `new \DateTimeImmutable()` inside a contract: a contract that depends on the current time
can fail at random.

**Strings.** Use `\mb_strlen()` for user-visible lengths and `\trim($name) !== ''` for "not blank".

**Collections.** `'\count($items) > 0'`, `'\array_is_list($items)'`, every item:
`'\count(\array_filter($items, static fn(int $i): bool => $i <= 0)) === 0'`, uniqueness:
`'\count(\array_unique($ids)) === \count($ids)'`. Every expression runs on every call, so move an expensive
check into a private method and keep it linear.

**Uninitialized properties.** An invariant that reads a typed property before it is set throws an `Error`, which is
reported as an `InvariantViolation` after the constructor. Initialize every property that an invariant reads.

**Side effects.** A contract must not assign, increment, call mutating methods, log or perform I/O. Contracts are
usually off in production, so the program must behave the same without them.
