<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional\Ensure;

use PhpDeal\Annotation as Contract;
use Go\Lang\Annotation\Pointcut;

class Stub
{
    private $privateValue = 42;

    /**
     * Method that changes internal state and ensures a contract for this
     * @param float $amount
     *
     * @Contract\Ensure("$this->privateValue == $__old->privateValue + $amount")
     * @Contract\Ensure("$this->privateValue > 0")
     */
    public function increment($amount)
    {
        $this->privateValue += $amount;
    }

    /**
     * BAD Method that changes internal state and ensures a contract for this
     * @param float $amount
     *
     * @Contract\Ensure("$this->privateValue == $__old->privateValue + $amount")
     */
    public function badIncrement($amount)
    {
        $this->privateValue += ($amount - 0.1); // Dirty hacker wants to earn money!
    }

    /**
     * Let's check that this getter is not cheating
     *
     * @Contract\Ensure("$__result == $this->privateValue")
     */
    public function returnPrivateValue()
    {
        return $this->privateValue;
    }

    /**
     * Let's check that aspect doesn't pay an attention to unknown annotations
     *
     * @Contract\Ensure("$__result == $this->privateValue")
     * @Pointcut("")
     */
    public function unknown()
    {

    }
}
