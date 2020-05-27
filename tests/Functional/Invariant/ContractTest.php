<?php

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace PhpDeal\Functional\Invariant;

use PhpDeal\Exception\ContractViolation;
use PHPUnit\Framework\TestCase;

class ContractTest extends TestCase
{
    /**
     * @var Stub
     */
    private $stub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stub = new Stub();
    }

    protected function tearDown(): void
    {
        unset($this->stub);
        parent::tearDown();
    }

    public function testInvariantValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->stub->accelerate(10, 30); // let's have a speed 300m/s
    }

    public function testInvariantViolated(): void
    {
        $this->expectException(ContractViolation::class);
        $this->stub->accelerate(10, 3e7); // let's have a speed 3*1e8 m/s, faster than light!
    }

    public function testInvariantViolatedAfterSeveralMethods(): void
    {
        $this->expectException(ContractViolation::class);
        $this->stub->accelerate(10, 30); // let's have a speed 300m/s
        $this->stub->decelerate(20, 20); // Negative speed?
    }
}
