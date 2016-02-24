<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Demo;

use PhpDeal\Annotation as Contract;

/**
 * Simple trade account contract
 */
interface AccountContractInterface
{
    /**
     * Deposits fixed amount of money to the account
     *
     * @param float $amount
     *
     * @Contract\Verify("$amount>0 && is_numeric($amount)")
     * @Contract\Ensure("$this->balance == $__old->balance+$amount")
     */
    public function deposit($amount);

    /**
     * Returns current balance
     *
     * @Contract\Ensure("$__result == $this->balance")
     *
     * @return float
     */
    public function getBalance();
}
