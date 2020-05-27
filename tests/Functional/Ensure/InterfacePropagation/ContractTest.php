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

namespace PhpDeal\Functional\Ensure\InterfacePropagation;

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

    public function providerEnsureValid(): array
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
    public function testEnsureValid(int $variable): void
    {
        $this->expectNotToPerformAssertions();
        $this->stub->add($variable);
    }

    public function providerEnsureInvalid(): array
    {
        return [
            [
                // invalid for Stub
                'variable' => 0
            ],
            [
                // invalid for StubInterfaceA
                'variable' => -1
            ],
            [
                // invalid for StubInterfaceB
                'variable' => -2
            ],
        ];
    }

    /**
     * @param int $variable
     * @dataProvider providerEnsureInvalid
     */
    public function testEnsureInvalid(int $variable): void
    {
        $this->expectException(ContractViolation::class);
        $this->stub->add($variable);
    }
}
