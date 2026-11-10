Changelog
=========

All notable changes to this project are documented in this file. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/).

2.0.0 - unreleased
------------------

A rewrite for modern PHP and for AI-driven development: an Agent Skill lets AI coding agents turn business
specifications into contracted code and tests. See [UPGRADE-2.0.md](UPGRADE-2.0.md) for the migration steps.

### Added

- Native PHP attributes `#[Verify]`, `#[Ensure]`, `#[Invariant]` and `#[Inherit]` in `PhpDeal\Attribute`; contracts
  are repeatable and implement the `PhpDeal\Attribute\Contract` interface.
- Dedicated exceptions `PreconditionViolation`, `PostconditionViolation` and `InvariantViolation`, the
  `ExceptionInterface` marker and `InvalidContractException` for broken contracts.
- `ContractViolation` exposes the violated `$contract`, the `$className` and the `$methodName`.
- `ContractApplication::registerAspects()` to enable contracts from an existing Go! AOP kernel.
- Contracts on static methods.
- Class-level `#[Inherit]` enables the contracts of parents for every public method.
- Invariants are checked after the constructor.
- PHPStan extension: properties and methods used only by contracts are not reported as unused.
- GitHub Actions CI on PHP 8.4 and 8.5, PHPStan level 10 with strict rules, PHP-CS-Fixer.
- Agent Skill `design-by-contract` for AI coding agents: turns business specifications into contracted code and
  PHPUnit tests derived from the contracts. It is installable as a Claude Code plugin from this repository
  (`/plugin marketplace add php-deal/framework`) (#36).
- Static checker for contract expressions, `skills/design-by-contract/scripts/lint-contracts.php`.
- On PHP 8.5+ contract expressions are compiled into static closures while Go! AOP weaves the class, on the line of
  the attribute (new `check` argument of `#[Verify]`, `#[Ensure]` and `#[Invariant]`, filled in by PhpDeal). They are
  cached by opcache, run without `eval()`, and breakpoints and stack traces point to the contract. Expressions that
  could behave differently as a static closure, and PHP 8.4, keep using `eval()` (#76).

### Changed

- Requires PHP 8.4+ and Go! AOP.
- Each contract expression is compiled once and cached; contracts are read once per method and class.
- Contracts of parent classes are evaluated in the scope of the declaring class.
- Inherited preconditions are checked before the method body.
- The object is cloned for `$__old` only when a postcondition uses it.
- Less work per call: contracts, method parameters, invariants by class name and the cloneability of a class are
  looked up in per-process caches before any reflection call, and error locations are built only on errors (#80).
- Only the built-in `#[Verify]`, `#[Ensure]` and `#[Invariant]` attributes are evaluated; `BoundContract` accepts no
  other `Contract` implementation, so computed text can not reach `eval()`. Compiled contracts come from the string
  literal in the source (#85).
- Violations point to the line of the method that declares the contract.

### Fixed

- An invariant calling a public method of its own class no longer recurses endlessly.
- Arrays and objects among the arguments no longer break the violation message.
- Variadic arguments are passed to contracts as a list.
- A trailing `//` comment in an expression no longer breaks it.
- `$this` inside a string literal of a static method's contract no longer throws `InvalidContractException` (#84).

### Removed

- Doctrine annotations (`PhpDeal\Annotation`) and the dependency on `doctrine/annotations`.
- `AbstractContractAspect`, `InheritCheckerAspect` and the `PhpDeal\Contract\Fetcher\Parent` classes.
- Travis CI and Scrutinizer configuration.

1.x
---

See the [GitHub releases](https://github.com/php-deal/framework/releases) for the history of the 1.x line.
