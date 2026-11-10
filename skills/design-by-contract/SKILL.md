---
name: design-by-contract
description: >-
  Turns business specifications (requirements, user stories, acceptance criteria, domain rules) into self-checking
  PHP code with php-deal/framework: #[Verify] preconditions, #[Ensure] postconditions and #[Invariant] invariants,
  plus PHPUnit tests derived from those contracts. Use it to implement a spec in PHP with Design by Contract, to add
  contracts to existing PHP classes, to turn business rules into preconditions, postconditions or invariants, to
  generate tests from contracts, or to review and fix php-deal contract expressions.
license: MIT
compatibility: Needs PHP 8.4+ and php-deal/framework 2.x in the target project. PHPUnit is used for the tests.
---

# Design by Contract with PhpDeal

Turn a business specification into PHP code that checks its own rules on every call, and into tests that exercise
those rules. PhpDeal reads the contracts from native attributes. They hold PHP expressions as strings, and
[Go! AOP](https://github.com/goaop/framework) evaluates them around the method:

```php
use PhpDeal\Attribute\{Ensure, Invariant, Verify};

#[Invariant('$this->balance >= 0')]                      // always true between calls
class Account
{
    private int $balance = 0;

    #[Verify('$amount > 0')]                              // the caller's obligation, checked before the body
    #[Ensure('$this->balance === $__old->balance + $amount')] // the method's promise, checked after it
    public function deposit(int $amount): void
    {
        $this->balance += $amount;
    }
}
```

A broken contract throws `PreconditionViolation`, `PostconditionViolation` or `InvariantViolation`. A contract
violation always means a bug in the caller or in the method. It never means invalid user input.

## Workflow

Copy this checklist and work through it:

```
- [ ] 1. Check the project setup
- [ ] 2. Extract the rules from the specification and number them
- [ ] 3. Classify every rule: precondition, postcondition, invariant, business error or not a contract
- [ ] 4. Design the API: classes, interfaces, query methods for the preconditions
- [ ] 5. Write the code with its contracts
- [ ] 6. Run the contract checker, fix every error
- [ ] 7. Derive the tests and run them
- [ ] 8. Report the rule-to-contract table, the assumptions and the open questions
```

### 1. Check the project setup

- `composer.json` requires `php-deal/framework` (2.x) and PHP 8.4 or newer. If it does not, ask before adding the
  dependency with `composer require php-deal/framework`.
- Contracts are checked only in woven classes. `ContractApplication::getInstance()->init([...])` must run before the
  classes are autoloaded, in the front controller and in the test bootstrap. Its `includePaths` must cover the
  directory of the new classes. If the project already has its own Go! AOP kernel, it calls
  `ContractApplication::registerAspects($container)` instead. Without weaving the attributes do nothing, so tests
  pass without checking anything.
- See [references/setup.md](references/setup.md) for the snippets.

### 2. Extract the rules

Read the specification and list every statement that constrains state, input, output or behaviour. Give each one a
stable ID (`R1`, `R2`, ...), or keep the IDs the specification already uses. Quote the source wording. Do not invent
rules. When the specification is ambiguous or silent, write the question down and pick the most conservative
assumption, which is usually the stricter precondition.

### 3. Classify each rule

| The specification says...                                                        | Use                                        |
|-----------------------------------------------------------------------------------|--------------------------------------------|
| "only when", "must be", "at most", "requires": a condition the caller can check first | `#[Verify]` on the method                  |
| "after X...", "increases by", "returns", "never changes Y": an effect of the call | `#[Ensure]` with `$__old` / `$__result`    |
| "always", "never", "at all times": a fact about the object                         | `#[Invariant]` on the class or interface   |
| "is rejected with the message...", "show an error": input from people or systems  | Code that throws a domain exception, plus `#[Ensure]` for the success path |
| Timing, notifications, persistence, permissions, other services                   | Not a contract: integration tests or code  |

Two points need judgement:

- **Precondition or business error?** A precondition is a promise the caller can keep. It is off in production when
  the kernel is not started, and its violation is a programming error. When the specification describes a reaction
  to bad input (a message, a rejected status, a retry), implement that reaction in code. You can also add a
  precondition at the boundary where the input has already been validated. Give the caller a query method
  (`available()`, `canCancel()`) so that it can check a precondition before the call.
- **Strength.** Write the strongest contract the specification supports. Prefer
  `$this->total === $__old->total + $amount` to `$this->total > $__old->total`. Add frame conditions for state that
  must not change: `#[Ensure('$this->reserved === $__old->reserved')]`.

[references/spec-to-contracts.md](references/spec-to-contracts.md) has a catalogue of rule patterns with their
contracts: ranges, state machines, collections, uniqueness, implications, money and dates.

### 4. Design the API

- Put contracts on an interface when it will have several implementations, and mark each implementation with
  `#[Inherit]`. Without it the interface's preconditions do not apply, and a class that declares no contract
  attribute of its own is not checked at all.
- Keep complex predicates in a private method (`#[Verify('$this->isValidTransition($to)')]`). Contracts run in the
  scope of the class, so they can call private methods and read private properties.
- Expose cheap public query methods for the state that preconditions depend on.

### 5. Write the code

Expression rules. Every one of them breaks contracts silently when ignored:

- Write expressions in **single quotes**: `#[Verify('$amount > 0')]`. In double quotes PHP would interpolate `$amount`.
- **No `use` imports inside expressions.** Write class names fully qualified (`\App\Money::isValid($amount)`), and
  global functions with a leading backslash (`\count($items)`). `self::`, `static::` and `parent::` work.
- Arguments are available by parameter name, with defaults filled in. A variadic parameter is a list.
- `$__result` (return value) and `$__old` (clone of the object before the call) exist only in `#[Ensure]`. `$__old`
  is a **shallow** clone: compare scalars, or add a `__clone()` that copies mutable objects.
- A contract returns `true` (holds), `false` (violated) or `null` (for assertion libraries). Any other value throws
  `InvalidContractException`.
- Contracts must have no side effects. Do not use `$this` in static methods.
- Postconditions and invariants are not checked when the method throws.
- Invariants are checked after the constructor and after every **public** method, not after private ones.

[references/expressions.md](references/expressions.md) has the full reference and the pitfalls of floats, dates,
enums, collections and nullables.

Add the rule ID next to each contract, as a trailing comment or in the method docblock, so that a reader can trace
it back to the specification.

### 6. Check the contracts

PHP and PHPStan do not see inside the expression strings. Run the bundled checker on the files you wrote:

```bash
php ${CLAUDE_SKILL_DIR}/scripts/lint-contracts.php --strict src/Inventory
```

`${CLAUDE_SKILL_DIR}` is the directory of this file. In projects with php-deal installed the checker is also at
`vendor/php-deal/framework/skills/design-by-contract/scripts/lint-contracts.php`. It reports syntax errors, unknown
or misspelled variables, `$__old` or `$__result` in the wrong contract, `$this` in static methods, imported names
that do not resolve, double-quoted strings and assignments. Fix every error and warning. It can not check types or
the existence of properties: the tests in the next step do that.

### 7. Derive the tests

Postconditions and invariants are checked on every call of a woven class. Any test that drives the object through
valid calls therefore also tests them. Write these tests:

1. **Specification examples.** Each example or acceptance criterion becomes a test with explicit assertions. This is
   the independent oracle: a wrong contract and wrong code can agree with each other.
2. **Precondition boundaries.** For each `#[Verify]`, call just inside the boundary (it passes) and just outside it
   (`PreconditionViolation`, and the state is unchanged). This test also proves that weaving is active.
3. **Business errors.** For each rejected input, assert the domain exception and its message.
4. **Random valid sequences.** Sample arguments from the preconditions with a seeded `Random\Randomizer` and run a
   few hundred calls. The contracts check every step.

[references/testing.md](references/testing.md) explains each kind and shows the bootstrap. Run the tests. When one
of them reports a violation, decide whether the code, the contract or the test is wrong. Do not weaken a contract to
make a test pass unless the specification supports the weaker version.

### 8. Report

End with:

- a table `Rule | Contract or code | Class::method | Test`, with one row per rule;
- the assumptions you made, and the open questions for the business;
- the rules that are not contracts, and where they are handled instead.

## Worked example

[examples/inventory/](examples/inventory) holds a complete run: [SPEC.md](examples/inventory/SPEC.md) (rules
R1-R9), [StockItem.php](examples/inventory/StockItem.php) with its contracts,
[CountBelowReserved.php](examples/inventory/CountBelowReserved.php) for the one business error, and
[StockItemTest.php](examples/inventory/StockItemTest.php) with the four kinds of tests. Read it before the first
implementation in a project. The PhpDeal test suite runs it, so it always works.
