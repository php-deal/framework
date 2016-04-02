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

    public function providerEnsureValid()
    {
        return [
            [
                'variable' => 50,
            ]
        ];
    }

    /**
     * @param int $variable
     * @dataProvider providerEnsureValid
     */
    public function testEnsureValid($variable)
    {
        $this->stub->add($variable);
    }

    public function providerEnsureInvalid()
    {
        return [
            [
                // invalid for Stub
                'variable' => 1
            ],
            [
                // invalid for StubParent
                'variable' => 2
            ],
            [
                // invalid for StubGrandparent
                'variable' => -1
            ],
        ];
    }

    /**
     * @param int $variable
     * @dataProvider providerEnsureInvalid
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testEnsureInvalid($variable)
    {
        $this->stub->add($variable);
    }
}
