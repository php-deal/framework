<?php

declare(strict_types=1);

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional\Invariant\InterfacePropagation;

use PhpDeal\Exception\ContractViolation;
use PHPUnit\Framework\TestCase;

/**
 * @group propagation
 */
class ContractTest extends TestCase
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

    public function providerInvariantValid(): array
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
    public function testInvariantValid(int $variable): void
    {
        $this->expectNotToPerformAssertions();
        $this->stub->setVariable($variable);
    }

    public function providerInvariantInvalid(): array
    {
        return [
            [
                // restriction of Stub
                'variable' => 1
            ],
            [
                // restriction of StubInterfaceA
                'variable' => 2
            ],
            [
                // restriction of StubInterfaceB
                'variable' => 3
            ],
        ];
    }

    /**
     * @param int $variable
     * @dataProvider providerInvariantInvalid
     */
    public function testInvariantInvalid($variable): void
    {
        $this->expectException(ContractViolation::class);
        $this->stub->setVariable($variable);
    }
}
