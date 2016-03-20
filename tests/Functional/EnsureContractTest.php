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

use PhpDeal\Stub\EnsureStub;

class EnsureContractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EnsureStub
     */
    private $stub;

    public function setUp()
    {
        parent::setUp();
        $this->stub = new EnsureStub();
    }

    public function tearDown()
    {
        unset($this->stub);
        parent::tearDown();
    }

    public function testEnsureValid()
    {
        $this->stub->increment(50);
    }

    /**
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testEnsureInvalid()
    {
        $this->stub->badIncrement(40);
    }

    public function testEnsureCanHandleResult()
    {
        $this->stub->returnPrivateValue();
    }
}
