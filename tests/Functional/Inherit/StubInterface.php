<?php

namespace PhpDeal\Functional\Inherit;

use PhpDeal\Annotation as Contract;

interface StubInterface
{
    /**
     * @Contract\Verify("$amount > 0 && is_numeric($amount)")
     * @param integer $amount
     * @return void
     */
    public function add($amount);

    /**
     * @Contract\Ensure("$__result === $this->amount")
     */
    public function getAmount();
}
