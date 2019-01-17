<?php

namespace PhpDeal\Functional\Inherit;

class StubWithoutInherit implements StubInterface
{
    private $amount = 0;

    public function add($amount)
    {
        $this->amount = $amount;
    }

    public function getAmount()
    {
        return 10;
    }
}
