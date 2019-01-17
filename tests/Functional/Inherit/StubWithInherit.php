<?php

namespace PhpDeal\Functional\Inherit;

use PhpDeal\Annotation as Contract;

class StubWithInherit implements StubInterface
{
    private $amount = 0;

    /**
     * @Contract\Inherit()
     * @param integer $amount
     * @return void
     */
    public function add($amount)
    {
        $this->amount += $amount;
    }

    /**
     * @Contract\Inherit()
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
