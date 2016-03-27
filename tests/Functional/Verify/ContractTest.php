<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional\Verify;

class ContractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Stub
     */
    private $stub;

    public function setUp()
    {
        parent::setUp();
        $this->stub = new Stub();
    }

    public function tearDown()
    {
        unset($this->stub);
        parent::tearDown();
    }

    public function testVerifyValid()
    {
        $this->stub->testNumeric(-200);
    }

    /**
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testVerifyInvalid()
    {
        $this->stub->testNumeric('message');
    }

    public function testAccessToPrivateFields()
    {
        $this->stub->testAccessToPrivateField(50);
    }

    public function testVerifyWithAssertValid()
    {
        $this->stub->add(100);
    }

    public function providerVerifyWithAssertInvalid()
    {
        return [
            [
                'value' => ""
            ],
            [
                'value' => 5.5
            ],
            [
                'value' => null
            ],
            [
                'value' => []
            ]
        ];
    }

    /**
     * @param mixed $value
     * @dataProvider providerVerifyWithAssertInvalid
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testVerifyWithAssertInvalid($value)
    {
        $this->stub->add($value);
    }

    public function testVerifyManyContractsValid()
    {
        $this->stub->sub(10);
    }

    public function providerVerifyManyContractsInvalid()
    {
        return [
            [
                'value' => ""
            ],
            [
                'value' => 1
            ],
            [
                'value' => null
            ],
            [
                'value' => []
            ]
        ];
    }

    /**
     * @param mixed $value
     * @dataProvider providerVerifyManyContractsInvalid
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testVerifyManyContractsInvalid($value)
    {
        $this->stub->sub($value);
    }
}
