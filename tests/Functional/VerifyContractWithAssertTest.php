<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional;

use PhpDeal\Stub\VerifyStubWithAssert as Stub;
use PhpDeal\Exception\ContractViolation;

/**
 * @author Piotr Dawidiuk
 */
class VerifyContractWithAssertTest extends \PHPUnit_Framework_TestCase
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
        $this->stub->add(100);
    }

    public function providerVerifyInvalid()
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
     *
     * @dataProvider providerVerifyInvalid
     */
    public function testVerifyInvalid($value)
    {
        $this->setExpectedException(ContractViolation::class);
        $this->stub->add($value);
    }
} 
