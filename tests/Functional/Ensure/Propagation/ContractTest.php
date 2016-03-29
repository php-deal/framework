<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional\Ensure\Propagation;

/**
 * @group propagation
 */
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

    public function providerAddValid()
    {
        return [
            [
                'variable' => 50,
            ]
        ];
    }

    /**
     * @param int $variable
     * @dataProvider providerAddValid
     */
    public function testAddValid($variable)
    {
        $this->stub->add($variable);
    }

    public function providerAddInvalid()
    {
        return [
            [
                // invalid for Stub
                'variable' => 0
            ],
            [
                // invalid for StubGrandparent
                'variable' => ""
            ],
            [
                // invalid for StubParent
                'variable' => 101
            ],
        ];
    }

    /**
     * @param int $variable
     * @dataProvider providerAddInvalid
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testAddInvalid($variable)
    {
        $this->stub->add($variable);
    }
} 
