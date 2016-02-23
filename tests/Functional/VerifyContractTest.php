<?php
namespace PhpDeal\Functional;

use PhpDeal\Stub\VerifyStub;

class VerifyContractTest extends \PHPUnit_Framework_TestCase
{
    public function testVerifyValid()
    {
        $verifyStub = new VerifyStub();
        $verifyStub->testNumeric(-200);
    }

    public function testVerifyInvalid()
    {
        $this->setExpectedException('PhpDeal\Exception\ContractViolation');
        $verifyStub = new VerifyStub();
        $verifyStub->testNumeric('message');
    }

    public function testAccessToPrivateFields()
    {
        $verifyStub = new VerifyStub();
        $verifyStub->testAccessToPrivateField(50);
    }
}
