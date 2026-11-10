<div align="center">

# PhpDeal

**Design by Contract for modern PHP**

Declare preconditions, postconditions and invariants with native attributes.<br>
PhpDeal checks them on every call, powered by [Go! AOP](https://github.com/goaop/framework).

[![CI](https://github.com/php-deal/framework/actions/workflows/ci.yml/badge.svg)](https://github.com/php-deal/framework/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/php-deal/framework.svg?label=release)](https://packagist.org/packages/php-deal/framework)
[![Total Downloads](https://img.shields.io/packagist/dt/php-deal/framework.svg)](https://packagist.org/packages/php-deal/framework)
[![PHP Version](https://img.shields.io/badge/php-%E2%89%A5%208.4-777BB4.svg?logo=php&logoColor=white)](https://www.php.net/releases/8.4/)
[![Go! AOP](https://img.shields.io/badge/Go!%20AOP-4.x-1f6feb.svg)](https://github.com/goaop/framework)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%2010-2a5ea7.svg)](https://phpstan.org/user-guide/rule-levels)
[![License](https://img.shields.io/packagist/l/php-deal/framework.svg)](LICENSE)

</div>

```php
use PhpDeal\Attribute\{Ensure, Invariant, Verify};

#[Invariant('$this->balance >= 0')]
class Account
{
    private float $balance = 0.0;

    #[Verify('$amount > 0')]
    #[Ensure('$this->balance === $__old->balance + $amount')]
    public function deposit(float $amount): void
    {
        $this->balance += $amount;
    }
}

new Account()->deposit(-10);
// PhpDeal\Exception\PreconditionViolation:
// Precondition "$amount > 0" violated in Account->deposit(-10.0)
```

## Contents

- [Why Design by Contract?](#why-design-by-contract)
- [Features](#features)
- [Installation](#installation)
- [Setup](#setup)
- [Contracts](#contracts)
  - [Preconditions: `#[Verify]`](#preconditions-verify)
  - [Postconditions: `#[Ensure]`](#postconditions-ensure)
  - [Invariants: `#[Invariant]`](#invariants-invariant)
  - [Inheritance: `#[Inherit]`](#inheritance-inherit)
- [Writing expressions](#writing-expressions)
- [Handling violations](#handling-violations)
- [PHPStan integration](#phpstan-integration)
- [Production](#production)
- [Upgrading from 1.x](#upgrading-from-1x)
- [Contributing](#contributing)

## Why Design by Contract?

[Design by Contract](https://en.wikipedia.org/wiki/Design_by_contract) treats the public API of a class as a formal
agreement between the class and its callers:

- **Preconditions** are the requirements upon the caller: what must be true before a method is called.
- **Postconditions** are the promises of the method: what is true after it returns.
- **Invariants** are the properties of an object that always hold between calls.

If the caller meets the requirements, the class delivers the promised service. A broken contract is a bug, and
PhpDeal reports it at the exact place where it happens instead of letting a corrupt state travel through the
application. The contracts live next to the code they describe, so they double as precise, executable
documentation.

## Features

- ✅ **Native PHP attributes**: `#[Verify]`, `#[Ensure]`, `#[Invariant]` and `#[Inherit]`, repeatable where it makes sense.
- 🧬 **Contract inheritance** from parent classes and interfaces, with clear and predictable rules.
- 🔒 **Full access to the object**: expressions run in the scope of the class and can read private state.
- 🕰️ **`$__old` and `$__result`** to compare the state before and after a call, and to check return values.
- 🎯 **Precise exceptions**: `PreconditionViolation`, `PostconditionViolation` and `InvariantViolation`, pointing to
  the method that declares the contract.
- ⚡ **Fast**: each expression is compiled once, contracts are read once per class, and the object is cloned only when
  a contract uses `$__old`.
- 🔁 **Recursion-safe**: an invariant may call public methods of its own class.
- 🧩 **Works with assertion libraries** such as [beberlei/assert](https://github.com/beberlei/assert).
- 🔍 **PHPStan extension** included: properties and methods used only by contracts are not reported as unused.
- 🧪 **Thoroughly tested**, analysed with PHPStan at level 10 with strict rules.

## Installation

```bash
composer require php-deal/framework
```

PhpDeal 2 requires **PHP 8.4** or newer and is built on **Go! AOP 4**.

## Setup

Initialize the contract kernel once, as early as possible in your front controller or test bootstrap, before the
classes with contracts are loaded:

```php
use PhpDeal\ContractApplication;

require __DIR__ . '/../vendor/autoload.php';

ContractApplication::getInstance()->init([
    'debug'        => true,
    'appDir'       => __DIR__ . '/..',
    'cacheDir'     => __DIR__ . '/../var/cache/contracts',
    'includePaths' => [__DIR__ . '/../src'],
]);
```

| Option         | Purpose                                                                                     |
|----------------|---------------------------------------------------------------------------------------------|
| `debug`        | `true` during development: changed classes are woven again automatically.                  |
| `appDir`       | Root directory of the application, usually the directory that contains `vendor/`.          |
| `cacheDir`     | Writable directory for the woven classes.                                                    |
| `includePaths` | Directories with the classes that declare contracts. Keep it narrow, it speeds up weaving. |
| `excludePaths` | Directories that are never woven.                                                            |

All [Go! AOP kernel options](https://github.com/goaop/framework/blob/master/docs/configuration.md) are supported.

### Already using Go! AOP?

Go! AOP runs a single kernel per application. Register the contract aspects in your own kernel instead of starting
`ContractApplication`:

```php
use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use PhpDeal\ContractApplication;

final class ApplicationAspectKernel extends AspectKernel
{
    protected function configureAop(AspectContainer $container): void
    {
        ContractApplication::registerAspects($container);
        // ...your own aspects
    }
}
```

### Symfony

Initialize the kernel in `public/index.php` (and in `tests/bootstrap.php`) right after the Composer autoloader, and
point `includePaths` to the `src` directory, not to the document root:

```php
ContractApplication::getInstance()->init([
    'debug'        => $_SERVER['APP_DEBUG'] ?? false,
    'appDir'       => dirname(__DIR__),
    'cacheDir'     => dirname(__DIR__) . '/var/cache/contracts',
    'includePaths' => [dirname(__DIR__) . '/src'],
]);
```

## Contracts

| Attribute      | Target           | Checked                                    | Extra variables          | Inherited from parents |
|----------------|------------------|--------------------------------------------|--------------------------|------------------------|
| `#[Verify]`    | method           | before the method body                     | –                        | on request             |
| `#[Ensure]`    | method           | after the method returns                   | `$__result`, `$__old`    | always                 |
| `#[Invariant]` | class, interface | after the constructor and every public method | `$__result`           | always                 |
| `#[Inherit]`   | method, class    | enables the contracts of parents           | –                        | –                      |

Method arguments are available in every expression by their names, default values included. A variadic parameter is
a list of the passed values.

### Preconditions: `#[Verify]`

A precondition is a requirement upon the caller. It is checked before the method body runs, so the body never sees
invalid arguments:

```php
use PhpDeal\Attribute\Verify;

class Wallet
{
    private const array SUPPORTED = ['EUR', 'USD'];

    private float $balance = 100.0;

    #[Verify('$amount > 0')]
    #[Verify('$amount <= $this->balance')]
    public function withdraw(float $amount): void
    {
        $this->balance -= $amount;
    }

    #[Verify('\in_array($currency, self::SUPPORTED, true)')]
    public static function convert(float $amount, string $currency = 'EUR'): float
    {
        return $amount; // static methods are supported as well
    }
}
```

The attribute is repeatable: every expression must hold.

### Postconditions: `#[Ensure]`

A postcondition is a promise of the method. It is checked after the method returns. Two extra variables are
available:

- `$__result` holds the value returned by the method;
- `$__old` is a clone of the object taken right before the call, to compare the state before and after.

```php
use PhpDeal\Attribute\Ensure;

class Counter
{
    private int $value = 0;

    #[Ensure('$this->value === $__old->value + 1')]
    public function increment(): void
    {
        ++$this->value;
    }

    #[Ensure('$__result >= 0')]
    public function getValue(): int
    {
        return $this->value;
    }
}
```

The object is cloned only when a contract of the method mentions `$__old`, and only when the class can be cloned.

### Invariants: `#[Invariant]`

An invariant describes a property of the object that holds whenever it is observed from the outside. It is checked
after the constructor and after every public method:

```php
use PhpDeal\Attribute\Invariant;

#[Invariant('$this->speed >= 0')]
#[Invariant('$this->speed < 299_792_458')] // not faster than light
class Particle
{
    public function __construct(private float $speed = 0) {}

    public function accelerate(float $acceleration, float $time): void
    {
        $this->speed += $acceleration * $time;
    }
}
```

Invariants of parent classes and interfaces always apply. An invariant can call public methods of its own class
without checking the invariant again.

### Inheritance: `#[Inherit]`

Contracts follow the class hierarchy, so an implementation can not quietly break the promises of its interface:

| Contract       | Inherited from parent classes and interfaces                                           |
|----------------|-----------------------------------------------------------------------------------------|
| `#[Ensure]`    | Always, when the method declares a postcondition itself or uses `#[Inherit]`            |
| `#[Invariant]` | Always, when the class declares an invariant itself or uses `#[Inherit]`                |
| `#[Verify]`    | Only on request: with `#[Inherit]`, or with an `{@inheritdoc}` tag in the doc comment   |

Preconditions are opt-in because a subclass may accept more than its parent (Liskov: *require no more, promise no
less*).

Use `#[Inherit]` to take over the contracts of the parents without declaring any of your own. On a method it enables
the preconditions and postconditions of the same method in every parent class and interface. On a class it does the
same for every public method and enables the invariants of the parents:

```php
use PhpDeal\Attribute\{Ensure, Inherit, Invariant, Verify};

#[Invariant('$this->getBalance() >= 0')]
interface AccountContract
{
    #[Verify('$amount > 0')]
    #[Ensure('$this->getBalance() === $__old->getBalance() + $amount')]
    public function deposit(float $amount): void;

    public function getBalance(): float;
}

#[Inherit] // every contract of AccountContract applies to this class
class Account implements AccountContract
{
    private float $balance = 0.0;

    public function deposit(float $amount): void
    {
        $this->balance += $amount;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }
}
```

All the contracts that apply to a call must hold. A class without any contract attribute, and without
`#[Inherit]`, is not checked even if its interfaces declare contracts.

## Writing expressions

A contract is a PHP expression in a string. It must return `true` when the contract holds and `false` when it is
violated. Returning `null` is accepted too, for assertion helpers that return nothing.

- **Scope.** The expression runs in the scope of the class that declares it, so it can read private and protected
  members with `$this->`, `self::` and `static::`. Contracts of an interface run in the scope of the implementing
  class. Static methods have no `$this`.
- **Names.** The expression is compiled outside of your file, so `use` imports do not apply: write class and function
  names fully qualified, e.g. `'\App\Money::isValid($amount)'` or `'Assert\Assertion::email($email)'`.
- **Errors.** An error or exception thrown by the expression is reported as a violation of the contract, with the
  original error as the previous exception. Assertion libraries can be used that way:

```php
#[Verify('Assert\Assertion::between($percent, 0, 100)')]
public function setDiscount(int $percent): void {}
```

- **Mistakes in contracts.** A syntax error, a `$this` in a static method or a result that is neither a boolean nor
  `null` throws an `InvalidContractException`: this is a bug in the contract itself, not a violation of it.

## Handling violations

Every exception of PhpDeal implements `PhpDeal\Exception\ExceptionInterface`:

```
ExceptionInterface
├── ContractViolation (LogicException)        a contract does not hold
│   ├── PreconditionViolation                 #[Verify]: the caller passed invalid arguments
│   ├── PostconditionViolation                #[Ensure]: the method did not keep its promise
│   └── InvariantViolation                    #[Invariant]: the object is in an invalid state
└── InvalidContractException (DomainException) the contract itself is broken
```

A violation tells exactly what went wrong and where. Its file and line point to the method that declares the contract:

```php
try {
    $account->withdraw(70);
} catch (PreconditionViolation $violation) {
    $violation->getMessage();  // Precondition "$amount > 0 && $amount <= 50" violated in Demo\Account->withdraw(70.0)
    $violation->contract;      // '$amount > 0 && $amount <= 50'
    $violation->className;     // 'Demo\Account'
    $violation->methodName;    // 'withdraw'
    $violation->getPrevious(); // the assertion exception or error raised by the expression, if any
}
```

Arguments in the message are rendered safely: long strings are truncated, and arrays and objects are reduced to their
size and class.

## PHPStan integration

Contract expressions are strings, so PHPStan can not see that a private property or method is used by a contract and
reports it as unused. PhpDeal ships a PHPStan extension that fixes this. With
[phpstan/extension-installer](https://github.com/phpstan/extension-installer) it is enabled automatically, otherwise
include it in your `phpstan.neon`:

```neon
includes:
    - vendor/php-deal/framework/extension.neon
```

## Production

Contracts are a development and testing tool: every checked call costs an expression evaluation. The usual setup is
to initialize `ContractApplication` only in development and test environments:

```php
if (($_SERVER['APP_ENV'] ?? 'prod') !== 'prod') {
    ContractApplication::getInstance()->init([/* ... */]);
}
```

Without the kernel the attributes are plain metadata, and your classes run untouched. If you keep contracts in
production, set `'debug' => false` and warm up the cache at deploy time, see the
[Go! AOP deployment guide](https://github.com/goaop/framework/blob/master/docs/configuration.md).

## Upgrading from 1.x

PhpDeal 2 replaces Doctrine annotations with native attributes. In short:

```diff
-use PhpDeal\Annotation as Contract;
+use PhpDeal\Attribute as Contract;

-/**
- * @Contract\Verify("$amount > 0 && is_numeric($amount)")
- * @Contract\Ensure("$this->balance == $__old->balance + $amount")
- */
+#[Contract\Verify('$amount > 0 && is_numeric($amount)')]
+#[Contract\Ensure('$this->balance == $__old->balance + $amount')]
 public function deposit($amount)
```

See [UPGRADE-2.0.md](UPGRADE-2.0.md) for every change, and the [CHANGELOG](CHANGELOG.md) for the new features.

## Demo

A small runnable example lives in [`demo/`](demo):

```bash
composer demo
```

## Contributing

Pull requests are welcome! Run `composer test` before submitting: it checks the coding style, runs PHPStan at
level 10 and the test suite. See [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

PhpDeal is open-source software licensed under the [MIT license](LICENSE).
