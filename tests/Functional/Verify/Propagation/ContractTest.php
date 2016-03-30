<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional\Verify\Propagation;

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

    public function providerVerifyInvalid()
    {
        return [
            [
                // Stub can't accept this value
                'parameter' => 1
            ]
        ];
    }

    /**
     * @param int $parameter
     * @dataProvider providerVerifyInvalid
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testVerifyInvalid($parameter)
    {
        $this->stub->add($parameter);
    }

    public function providerVerifyValid()
    {
        return [
            [
                // StubParent does not accept this parameter, but Stub accepts (and we don't have inheritdoc annotation)
                'parameter' => 2
            ]
        ];
    }

    /**
     * @param int $parameter
     * @dataProvider providerVerifyValid
     */
    public function testVerifyValid($parameter)
    {
        $this->stub->add($parameter);
    }
}
