<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional\Invariant\Propagation;

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

    public function providerInvariantValid()
    {
        return [
            [
                'variable' => 4
            ]
        ];
    }

    /**
     * @param int $variable
     * @dataProvider providerInvariantValid
     */
    public function testInvariantValid($variable)
    {
        $this->stub->setVariable($variable);
    }

    public function providerInvariantInvalid()
    {
        return [
            [
                // restriction of Stub
                'variable' => 1
            ],
            [
                // restriction of StubParent
                'variable' => 2
            ],
            [
                // restriction of StubGrandparent
                'variable' => 3
            ],
        ];
    }

    /**
     * @param int $variable
     * @dataProvider providerInvariantInvalid
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testInvariantInvalid($variable)
    {
        $this->stub->setVariable($variable);
    }
}
