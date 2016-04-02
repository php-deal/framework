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

[![Build Status](https://secure.travis-ci.org/lisachenko/php-deal.png?branch=master)](https://travis-ci.org/lisachenko/php-deal)
[![GitHub release](https://img.shields.io/github/release/lisachenko/php-deal.svg)](https://github.com/lisachenko/php-deal/releases/latest)
[![Code Coverage](https://scrutinizer-ci.com/g/lisachenko/php-deal/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lisachenko/php-deal/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lisachenko/php-deal/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lisachenko/php-deal/?branch=master)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.5-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/lisachenko/php-deal.svg)](https://packagist.org/packages/lisachenko/php-deal)
Installation
------------

PhpDeal framework can be installed with composer. Installation is quite easy, just ask composer to download
the framework with its dependencies by running the command:

``` bash
$ php composer.phar require lisachenko/php-deal
```

IDE Integration
---------------
To improve your productivity with PhpStorm, you should definitely install a [Go! AOP Framework](https://plugins.jetbrains.com/plugin/7785) plugin (>=1.0.1) to have a PHP syntax highlighting for defining contracts and navigation to AOP advices.
<img src="https://cloud.githubusercontent.com/assets/640114/14225436/fc3e63a8-f8d3-11e5-9131-5c2ecc84ef60.png" alt="PhpStorm example" width="500px" />


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

By definition, if a pre contract fails, then the body received bad parameters. An ContractViolation exception
is thrown.
If a post contract fails, then there is a bug in the body. An ContractViolation exception is thrown.

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

NOTE! The code in the invariant may not call any public non-static members of the class, either directly or
indirectly. Doing so will result in a stack overflow, as the invariant will wind up being called in an
infinitely recursive manner.

Contract propagation
----------

For preconditions (Verify contracts) subclasses do not inherit contracts of parents' methods if they don't have @inheritdoc annotation. Example:

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

Foo::bar accepts '2' literal as a parameter and does not accept '1'.

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

Foo::bar does not accept '1' and '2' literals as a parameter.




For postconditions (Ensure and Invariants contracts) subclasses inherit contracts and they don't need @inheritdoc. Example:

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

Foo::setBar does not accept '1' and '2' literals as a parameter.

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

