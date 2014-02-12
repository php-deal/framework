PhpDeal
-------

Design by Contract framework for PHP

What is Design by Contract?
---------------------------

The specification of a class or interface is the collection of non-private items provided as services to the
caller, along with instructions for their use, as stated in phpDoc. Design By Contract is an effective technology
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

Installation
------------

PhpDeal framework can be installed with composer. Installation is quite easy, just ask composer to download
the framework with its dependencies by running the command:

``` bash
$ php composer.phar require lisachenko/php-deal
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

