<?php
declare(strict_types=1);

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
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
     * @Contract\Verify("\is_numeric($variable)")
     * @return float
     */
    public function testNumeric(float $variable): float
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
    public function testAccessToPrivateField($variable): void
    {
    }

    /**
     * Method with contract integrated with beberlei/assert library
     *
     * @param int $value
     * @return bool
     * @Contract\Verify("Assert\Assertion::greaterThan($value, 5)")
     */
    public function add(int $value): bool
    {
        return true;
    }

    /**
     * Method with many contracts
     *
     * @param int $value
     * @return bool
     * @Contract\Verify("$value < 10")
     * @Contract\Verify("$value > 5")
     */
    public function sub(int $value): bool
    {
        return true;
    }
}
