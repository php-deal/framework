<?php
namespace PhpDeal\Functional;

use PhpDeal\Stub\EnsureStub;

class EnsureContractTest extends \PHPUnit_Framework_TestCase
{
    public function testEnsureValid()
    {
        $ensureStub = new EnsureStub();
        $ensureStub->increment(50);
    }

    public function testEnsureInvalid()
    {
        $this->setExpectedException('PhpDeal\Exception\ContractViolation');
        $ensureStub = new EnsureStub();
        $ensureStub->badIncrement(40);
    }

    public function testEnsureCanHandleResult()
    {
        $ensureStub = new EnsureStub();
        $ensureStub->returnPrivateValue();
    }
}
