<?php
namespace PhpDeal\Functional;

use PhpDeal\Exception\ContractViolation;
use PhpDeal\Stub\Speed;

class InvariantContractTest extends \PHPUnit_Framework_TestCase
{
    public function testInvariantValid()
    {
        $speed = new Speed();
        $speed->accelerate(10, 30); // let's have a speed 300m/s
    }

    public function testInvariantViolated()
    {
        $this->setExpectedException(ContractViolation::class);
        $speed = new Speed();
        $speed->accelerate(10, 3e7); // let's have a speed 3*1e8 m/s, faster than light!
    }

    public function testInvariantViolatedAfterSeveralMethods()
    {
        $this->setExpectedException(ContractViolation::class);
        $speed = new Speed();
        $speed->accelerate(10, 30); // let's have a speed 300m/s
        $speed->decelerate(20, 20); // Negative speed?
    }
}
