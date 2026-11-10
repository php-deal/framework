# AGENTS.md

Guidance for AI coding agents working on this repository. Human contributors: see [CONTRIBUTING.md](CONTRIBUTING.md).

## Project

`php-deal/framework` is a Design by Contract library for PHP. Contracts are native attributes
(`#[Verify]`, `#[Ensure]`, `#[Invariant]`, `#[Inherit]`) whose string arguments are PHP expressions. Aspects of
[Go! AOP 4](https://github.com/goaop/framework) weave the annotated classes and evaluate the expressions on every
call.

- PHP **8.4+** (CI runs 8.4 and 8.5). Do not use PHP 8.5-only features; PHPStan analyses for the 8.4–8.5 range.
- Runtime dependency: `goaop/framework ^4.0` only. Do not add runtime dependencies without a strong reason.
- Version 2.x is a major rewrite of 1.x (Doctrine annotations). Keep [UPGRADE-2.0.md](UPGRADE-2.0.md) and
  [CHANGELOG.md](CHANGELOG.md) in sync with behaviour changes.

## Commands

```bash
composer install
composer test        # everything below, in this order
composer cs          # PHP-CS-Fixer check (composer cs:fix to fix)
composer phpstan     # PHPStan level 10 + strict rules + phpunit extension
composer phpunit     # unit and functional test suites
composer demo        # runs demo/run.php, a smoke test of real weaving
```

Run `composer test` before you commit. All three checks must pass. CI (`.github/workflows/ci.yml`) also runs a
lowest-dependencies job and `composer audit`.

## Layout

| Path                         | Contents |
|------------------------------|----------|
| `src/Attribute/`             | The attributes. `Verify`, `Ensure`, `Invariant` implement the `Contract` interface (`$expression`). |
| `src/Aspect/`                | One Go! AOP aspect per contract kind. The pointcuts are in their advice attributes. |
| `src/Contract/ContractFetcher.php`   | Collects contracts from the method/class and its parents and interfaces, with caching. |
| `src/Contract/ContractEvaluator.php` | Compiles expressions to closures (once), binds them to the object/scope, evaluates them. |
| `src/Contract/BoundContract.php`     | A contract plus the class scope its expression runs in. |
| `src/Contract/ContractKind.php`      | Enum that maps a check to its violation exception. |
| `src/Exception/`             | `ContractViolation` and its subclasses, `InvalidContractException`, `ExceptionInterface`. |
| `src/PHPStan/`               | PHPStan extension (registered by `extension.neon`): members used only by contracts count as used. |
| `src/ContractApplication.php`| The aspect kernel; `registerAspects()` for apps that have their own kernel. |
| `tests/Fixtures/`            | Classes **woven** by the test bootstrap. Only this directory is in `includePaths`. |
| `tests/Functional/`          | Tests that call fixtures through real weaving. |
| `tests/Unit/`                | Tests without weaving; `tests/Unit/Stub/FakeInvocation.php` is a hand-made `MethodInvocation`. |
| `demo/`                      | Runnable example, also run in CI. |

## How it works (read before changing behaviour)

- **Pointcuts.** Preconditions use `#[Before]`; postconditions and invariants use `#[Around]`. Go! AOP 4 requires
  advice methods to be `public`, with one advice attribute per method.
- **Inheritance rules.** These are deliberate and documented in the README:
  - `Ensure` and `Invariant` always include the contracts of parent classes and interfaces.
  - `Verify` includes them only with `#[Inherit]` (on the method or class) or an `{@inheritdoc}` tag.
  - A class whose own methods carry no contract attribute and that has no `#[Inherit]` is not woven.
- **Scope.** A contract runs in the scope of the class that declares it (`BoundContract::$scope`), so parent
  contracts can read the parent's private state. Interface contracts run in the scope of the implementing class.
- **Variables.**
  - Arguments are available by parameter name, with defaults filled in; variadics become a list.
  - `$__result` holds the return value (postconditions and invariants).
  - `$__old` is a clone taken before the call. It is cloned only if an expression contains `$__old`.
- **Results.**
  - `false` means the contract is violated.
  - `true` or `null` means it holds (`null` exists for assertion libraries).
  - Any other result, a `ParseError`, or `$this` in a static method throws `InvalidContractException`.
  - A `Throwable` thrown by the expression becomes the `previous` exception of a violation.
- **Recursion guard.** While an expression is evaluated, `ContractEvaluator::isEvaluating()` is true and every aspect
  skips its checks. This lets an invariant call public methods of its own class.
- **Woven classes.** Go! AOP 4 turns the original class body into a `<Class>OriginalTrait`, and the proxy keeps the
  original class name. `ContractViolation` reports the location of the `...OriginalAlias` method, so file and line
  point to the user's source.

## Conventions

- `declare(strict_types=1);` everywhere, and the license header from existing `src/` files on new `src/` files.
- Coding style is PER-CS 2.0 plus the rules in `.php-cs-fixer.dist.php`:
  - Global functions are fully qualified (`\count()`).
  - Imports are sorted.
  - `=>` and `=` are aligned.
- Prefer `final` / `final readonly` classes and constructor promotion. Use typed constants and properties.
- PHPStan is at level 10. **Never** add `@phpstan-ignore`, baseline entries or `assert()`/`@var` casts to silence
  errors. Fix the types instead. Give generics explicit types (e.g. `ReflectionClass<covariant object>`).
- Contract expressions in fixtures, the README and the demo are single-quoted strings: `#[Verify('$amount > 0')]`.
  Global functions in expressions are written `\is_numeric(...)`, and class names fully qualified, because `use`
  imports do not apply inside expressions.
- Keep comments short and useful. Public API classes need a class docblock that explains their purpose.

## Testing tips

- Woven code is cached in `tests/cache/` (and in `demo/cache/` for the demo). If results look stale, delete it.
- A new class that must be woven goes in `tests/Fixtures/<Feature>/`, namespace
  `PhpDeal\Tests\Fixtures\<Feature>`, and is loaded through Composer. Classes loaded any other way are not woven.
- Use PHPUnit 13 attributes (`#[DataProvider]`, `#[TestWith]`, `#[CoversClass]`); data providers are `static`.
- PHPUnit fails on warnings, notices, deprecations and risky tests.
- The fixtures also test the PHPStan extension. If you remove a contract that reads a private property, PHPStan
  starts to report that property, which is the extension working as intended.

## Before you finish

1. `composer test` passes.
2. Behaviour changes are reflected in `README.md`, `CHANGELOG.md` and, if they break BC, `UPGRADE-2.0.md`.
3. README code examples still work: they are meant to be copy-paste runnable.
