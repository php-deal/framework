<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional\Invariant;

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

    public function testInvariantValid()
    {
        $this->stub->accelerate(10, 30); // let's have a speed 300m/s
    }

    /**
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testInvariantViolated()
    {
        $this->stub->accelerate(10, 3e7); // let's have a speed 3*1e8 m/s, faster than light!
    }

    /**
     * @expectedException \PhpDeal\Exception\ContractViolation
     */
    public function testInvariantViolatedAfterSeveralMethods()
    {
        $this->stub->accelerate(10, 30); // let's have a speed 300m/s
        $this->stub->decelerate(20, 20); // Negative speed?
    }
}
