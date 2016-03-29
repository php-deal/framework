<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional\Verify;

use PhpDeal\Annotation as Contract;
use Go\Lang\Annotation\Pointcut;

class Stub
{
    private $privateValue = 42;

    /**
     * Method with numeric parameter requirement
     *
     * @param float $variable
     * @Contract\Verify("is_numeric($variable)")
     * @return float
     */
    public function testNumeric($variable)
    {
        return $variable;
    }


    /**
     * Method with contract that access a private variable
     *
     * @param float $variable
     * @Contract\Verify("$variable > $this->privateValue")
     * @Pointcut("")
     */
    public function testAccessToPrivateField($variable)
    {
        return;
    }

    /**
     * Method with contract integrated with beberlei/assert library
     *
     * @param int $value
     * @return bool
     * @Contract\Verify("Assert\Assertion::integer($value)")
     */
    public function add($value)
    {
        return true;
    }

    /**
     * Method with many contracts
     *
     * @param int $value
     * @return bool
     * @Contract\Verify("is_numeric($value)")
     * @Contract\Verify("$value > 5")
     */
    public function sub($value)
    {
        return true;
    }
} 
