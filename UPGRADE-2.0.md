Upgrading from 1.x to 2.0
=========================

PhpDeal 2.0 moves to native PHP attributes and Go! AOP 4. Most applications only need the steps of the
quick checklist.

Quick checklist
---------------

1. Run on PHP 8.4 or newer, require `php-deal/framework:^2.0` and update the dependencies.
2. Replace the annotations with attributes (see below). `doctrine/annotations` is not used any more.
3. Delete the old cache directory: the Go! AOP 4 cache format is different.
4. If you catch `DomainException` for broken contracts, catch `PhpDeal\Exception\InvalidContractException` instead.

Requirements
------------

| 1.x                         | 2.0                                 |
|-----------------------------|-------------------------------------|
| PHP 7.1 – 7.4               | PHP 8.4+                            |
| goaop/framework ^2.3        | goaop/framework ^4.0 \|\| ^5.0      |
| doctrine/annotations        | native attributes                   |

Annotations become attributes
-----------------------------

The `PhpDeal\Annotation` namespace is replaced by `PhpDeal\Attribute`. Every contract is a single-quoted string
argument, so `$variables` need no escaping:

```diff
-use PhpDeal\Annotation as Contract;
+use PhpDeal\Attribute as Contract;

-/**
- * @Contract\Invariant("$this->balance >= 0")
- */
+#[Contract\Invariant('$this->balance >= 0')]
 class Account
 {
-    /**
-     * @Contract\Verify("$amount > 0")
-     * @Contract\Ensure("$this->balance == $__old->balance + $amount")
-     */
+    #[Contract\Verify('$amount > 0')]
+    #[Contract\Ensure('$this->balance == $__old->balance + $amount')]
     public function deposit(float $amount): void {}

-    /**
-     * @Contract\Inherit
-     */
+    #[Contract\Inherit]
     public function withdraw(float $amount): void {}
 }
```

The old restriction on curly braces in annotations is gone: an attribute argument can contain any PHP expression.

The attribute classes are `final readonly` and expose the expression as `$expression` (it was `$value`).
`__toString()` is removed.

Kernel
------

`ContractApplication` is initialized as before. To combine contracts with your own aspects, call
`ContractApplication::registerAspects($container)` from `configureAop()` of your own Go! AOP kernel instead of
extending `ContractApplication`.

The aspects are now `final readonly` services built from `ContractFetcher` and `ContractEvaluator`, and are
registered lazily. `AbstractContractAspect`, `InheritCheckerAspect` and the classes of
`PhpDeal\Contract\Fetcher\Parent` are removed: their behaviour lives in `PhpDeal\Contract\ContractFetcher`.

Behaviour changes
-----------------

- **Dedicated exceptions.** Violations are reported with `PreconditionViolation`, `PostconditionViolation` and
  `InvariantViolation`. All of them extend `ContractViolation`, so existing `catch (ContractViolation $e)` blocks keep
  working. The message format changed, e.g. `Precondition "$amount > 0" violated in App\Account->deposit(-1.0)`.
- **Broken contracts.** A contract with a syntax error, a contract that returns something else than a boolean or
  `null`, and a contract that uses `$this` in a static method throw `InvalidContractException` (a
  `DomainException`). In 1.x such problems were wrapped into a `ContractViolation`.
- **`#[Inherit]` checks preconditions first.** Inherited preconditions are checked before the method body runs; 1.x
  ran the body first and checked every inherited contract afterwards.
- **`#[Inherit]` on a class** now also inherits the preconditions and postconditions of every public method, as the
  documentation always described; 1.x inherited the invariants only.
- **Scope of parent contracts.** A contract declared on a parent class is evaluated in the scope of that class, so it
  can read the private state of the parent. Contracts of interfaces are evaluated in the scope of the implementing
  class, as before.
- **Invariants of the object.** The invariants of the runtime class of the object are checked, together with those of
  its parents and interfaces, also for methods inherited from a parent.
- **No recursion in invariants.** An invariant may call public methods of its own class; contracts of calls made while
  a contract is evaluated are not checked.
- **`$__old` is cloned on demand.** The object is cloned only for postconditions that use `$__old`, so `__clone()` is
  no longer called for every call of a method with a postcondition.
- **Arguments.** Variadic parameters are available as a list of values. Arguments are rendered safely in messages:
  arrays and objects no longer break the message (1.x used `implode()`).

Tooling
-------

The development tools moved to PHPUnit 13, PHPStan 2 (level 10) and PHP-CS-Fixer (PER-CS 2.0); Travis CI and
Scrutinizer are replaced by GitHub Actions. The package ships a PHPStan extension (see the README).
