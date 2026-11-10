Changelog
=========

All notable changes to this project are documented in this file. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/).

2.0.0 - unreleased
------------------

A rewrite for modern PHP. See [UPGRADE-2.0.md](UPGRADE-2.0.md) for the migration steps.

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

### Changed

- Requires PHP 8.4+ and Go! AOP 4.
- Each contract expression is compiled once and cached; contracts are read once per method and class.
- Contracts of parent classes are evaluated in the scope of the declaring class.
- Inherited preconditions are checked before the method body.
- The object is cloned for `$__old` only when a postcondition uses it.
- Violations point to the line of the method that declares the contract.

### Fixed

- An invariant calling a public method of its own class no longer recurses endlessly.
- Arrays and objects among the arguments no longer break the violation message.
- Variadic arguments are passed to contracts as a list.
- A trailing `//` comment in an expression no longer breaks it.

### Removed

- Doctrine annotations (`PhpDeal\Annotation`) and the dependency on `doctrine/annotations`.
- `AbstractContractAspect`, `InheritCheckerAspect` and the `PhpDeal\Contract\Fetcher\Parent` classes.
- Travis CI and Scrutinizer configuration.

1.x
---

See the [GitHub releases](https://github.com/php-deal/framework/releases) for the history of the 1.x line.
