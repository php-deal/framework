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
     * Withdraw amount of money from account.
     *
     * We don't allow withdrawal of more than 50
     * @Contract\Verify("$amount <= $this->balance")
     * @Contract\Verify("$amount <= 50")
     * @Contract\Ensure("$this->balance == $__old->balance-$amount")
     * @param float $amount
     */
    public function withdraw($amount);

    /**
     * Returns current balance
     *
     * @Contract\Ensure("$__result == $this->balance")
     *
     * @return float
     */
    public function getBalance();
}
