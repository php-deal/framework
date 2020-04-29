PhpDeal
-------

Design by Contract framework for PHP

What is Design by Contract?
---------------------------

The specification of a class or interface is the collection of non-private items provided as services to the
caller, along with instructions for their use, as stated in phpDoc. [Design By Contract](http://en.wikipedia.org/wiki/Design_by_contract) is an effective technology
for creating a specification.

The fundamental idea of Design By Contract is to treat the services offered by a class or interface as a
contract between the class (or interface) and its caller. Here, the word "contract" is meant to convey a kind
of formal, unambiguous agreement between two parties:

* requirements upon the caller made by the class
* promises made by the class to the caller

If the caller fulfills the requirements, then the class promises to deliver some well-defined service. Some
changes to a specification/contract will break the caller, and some won't. For determining if a change will
break a caller, C++ FAQs uses the memorable phrase "require no more, promise no less": if the new specification
does not require more from the caller than before, and if it does not promise to deliver less than before,
then the new specification is compatible with the old, and will not break the caller.

[![Build Status](https://api.travis-ci.org/php-deal/framework.png?branch=1.x)](https://travis-ci.org/php-deal/framework)
[![GitHub release](https://img.shields.io/github/release/php-deal/framework.svg)](https://github.com/php-deal/framework/releases/latest)
[![Code Coverage](https://scrutinizer-ci.com/g/php-deal/framework/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/php-deal/framework/?branch=1.x)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/php-deal/framework/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/php-deal/framework/?branch=1.x)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D7.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/php-deal/framework.svg)](https://packagist.org/packages/php-deal/framework)

Installation
------------

PhpDeal framework can be installed with composer. Installation is quite easy, just ask composer to download
the framework with its dependencies by running the command:

``` bash
$ composer require php-deal/framework
```

Setup
-----

Put the following code at the begining of your 
application entry point (or require it from an external file). 

```php

$instance = ContractApplication::getInstance();
$instance->init(array(
    'debug'    => true,
    'appDir'   => __DIR__,
    'excludePaths' => [
        __DIR__ . '/vendor'
    ],
    'includePaths' => [

    ],
    'cacheDir' => __DIR__.'/cache/',
));
```

Symfony setup
-------------

Put the following code in app_dev.php and adapt it to match
your folder structure. The appDir must point to the folder containing
the src files, not the document root folder ! 

```php

$instance = ContractApplication::getInstance();
$instance->init(array(
    'debug'    => true,
    'appDir'   => __DIR__ . '/../src',
    'excludePaths' => [

    ],
    'includePaths' => [

    ],
    'cacheDir' => __DIR__.'/var/cache/',
));
```


Pre and Post Contracts
----------------------

The pre contracts specify the preconditions (requirements) before a statement is executed. The most typical use
of this would be in validating the parameters to a function. The post contracts (promises) validate the result
of the statement. The most typical use of this would be in validating the return value of a method and of
any side effects it has. The syntax is:

```php
<?php
namespace Vendor\Namespace;

use PhpDeal\Annotation as Contract; //import DbC annotations

/**
 * Some account class
 */
class Account
{

    /**
     * Current balance
     *
     * @var float
     */
    protected $balance = 0.0;

    /**
     * Deposits fixed amount of money to the account
     *
     * @param float $amount
     *
     * @Contract\Verify("$amount>0 && is_numeric($amount)")
     * @Contract\Ensure("$this->balance == $__old->balance+$amount")
     */
    public function deposit($amount)
    {
        $this->balance += $amount;
    }
}
```

By definition, if a pre contract fails, then the body received bad parameters. A ContractViolation exception
is thrown.
If a post contract fails, then there is a bug in the body. A ContractViolation exception is thrown.

Invariants
----------

Invariants are used to specify characteristics of a class that always must be true (except while executing a
protected or private member function).

The invariant is a contract saying that the asserts must hold true. The invariant is checked when a class constructor
 completes and at the end of the class public methods:

```php
<?php
namespace Vendor\Namespace;

use PhpDeal\Annotation as Contract; //import DbC annotations

/**
 * Some account class
 *
 * @Contract\Invariant("$this->balance > 0")
 */
class Account
{

    /**
     * Current balance
     *
     * @var float
     */
    protected $balance = 0.0;

    /**
     * Deposits fixed amount of money to the account
     *
     * @param float $amount
     */
    public function deposit($amount)
    {
        $this->balance += $amount;
    }
}
```
Invariants contain assert expressions, and so when they fail, they throw a ContractViolation exception.

__NOTE__: The code in the invariant may not call any public non-static members of the class, either directly or
indirectly. Doing so will result in a stack overflow, as the invariant will wind up being called in an
infinitely recursive manner.

Contract propagation
----------

There a some differences in inheritance of the contracts:

1. Ensure
  - if provided `Ensure` will automatically inherit all contracts from parent class or interface
2. Verify
  - if provided `Verify` will _not_ inherit contracts from parent class or interface
  - to inherit contracts you will need to provide `@inheritdoc` or the `Inherit` contract
3. Invariant
  - if provided `Invariant` will inherit all contracts from parent class or interface
4. Inherit
  - if provided `Inherit` will inherit all contracts from the given level (class, method) without the
  need to provide a contract on your current class or method
  
__Notes__: 
- The parsing of a contract only happens __IF__ you provide any given annotation from this package.
Without it, your contracts won't work!
- The annotation __must not__ have curly braces (`{}`) otherwise the annotation reader can't find them.

```php

class Foo extends FooParent
{
    /**
     * @param int $amount
     * @Contract\Verify("$amount != 1")
     */
    public function bar($amount)
    {
        ...
    }
}
    
class FooParent
{
    /**
     * @param int $amount
     * @Contract\Verify("$amount != 2")
     */
    public function bar($amount)
    {
        ...
    }
}
    
```

`Foo::bar` accepts `2` literal as a parameter and does not accept `1`.

With @inheritdoc:

```php

class Foo extends FooParent
{
    /**
     * @param int $amount
     * @Contract\Verify("$amount != 1")
     * {@inheritdoc}
     */
    public function bar($amount)
    {
        ...
    }
}
    
class FooParent
{
    /**
     * @param int $amount
     * @Contract\Verify("$amount != 2")
     */
    public function bar($amount)
    {
        ...
    }
}
    
```

`Foo::bar` does not accept `1` and `2` literals as a parameter.


For postconditions (Ensure and Invariants contracts) subclasses inherit contracts and they don't need `@inheritdoc`. Example:

```php
    
/**
 * @Contract\Invariant("$this->amount != 1")
 */
class Foo extends FooParent
{
    
}

/**
 * @Contract\Invariant("$this->amount != 2")
 */
class FooParent
{
    /**
     * @var int
     */
    protected $amount;
    
    /**
     * @param int $amount
     */
    protected function setBar($amount)
    {
        $this->amount = $amount;
    }
}
    
```

`Foo::setBar` does not accept `1` and `2` literals as a parameter.

If you don't want to provide a contract on your curent method/class you can use the `Inherit` annotation:

```php
class Foo extends FooParent
{
    /**
     * @param int $amount
     * @Contract\Inherit
     */
    public function bar($amount)
    {
        ...
    }
}
    
class FooParent
{
    /**
     * @param int $amount
     * @Contract\Verify("$amount != 2")
     */
    public function bar($amount)
    {
        ...
    }
}
```

`Foo:bar()` does accept eveything, except: `2`

Integration with assertion library
----------

To enhance capabilities of contracts, it's possible to use [assertion library](https://github.com/beberlei/assert).
```php
    /**
     * Deposits fixed amount of money to the account
     *
     * @param float $amount
     *
     * @Contract\Ensure("Assert\Assertion::integer($this->balance)")
     */
    public function deposit($amount)
    {
        $this->balance += $amount;
    }
```

[More assertions](https://github.com/beberlei/assert#list-of-assertions)

IDE Integration
---------------
To improve your productivity with PhpStorm, you should definitely install a [Go! AOP Framework](https://plugins.jetbrains.com/plugin/7785) plugin (>=1.0.1) to have a PHP syntax highlighting for defining contracts and navigation to AOP advices.
<img src="https://cloud.githubusercontent.com/assets/640114/14225436/fc3e63a8-f8d3-11e5-9131-5c2ecc84ef60.png" alt="PhpStorm example" width="500px" />


Common issues
-----------

##### Fatal error: Uncaught Error: Class 'Go\ParserReflection\Instrument\PathResolver'
```php
Fatal error: Uncaught Error: Class 'Go\ParserReflection\Instrument\PathResolver' 
not found in .../vendor/goaop/parser-reflection/src/ReflectionEngine.php on line XXX
```

This happens if your `appDir` configuration points at the same level as your `vendor` directory.
To solve this issue try adding your `vendor` folder into the `excludePaths` configuration.

```php
ContractApplication::getInstance()->init(array(
    'debug'    => true,
    'appDir'   => __DIR__,,
    'excludePaths' => [
        __DIR__ . '/vendor'
    ],
    'cacheDir' => __DIR__.'/cache/',
));
```
