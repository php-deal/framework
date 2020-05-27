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

namespace PhpDeal\Functional\Verify;

use PhpDeal\Exception\ContractViolation;
use PHPUnit\Framework\TestCase;

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

    public function testVerifyValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->stub->testNumeric(-200);
    }

    public function testAccessToPrivateFields(): void
    {
        $this->expectNotToPerformAssertions();
        $this->stub->testAccessToPrivateField(50);
    }

    public function testVerifyWithAssertValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->stub->add(100);
    }

    /**
     * This test should fail with php TypeError.
     */
    public function testVerifyWithAssertInvalid(): void
    {
        $this->expectException(ContractViolation::class);
        $this->stub->add(1);
    }

    public function testVerifyManyContractsValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->stub->sub(9);
    }

    public function providerVerifyManyContractsInvalid(): array
    {
        return [
            [
                'value' => 11
            ],
            [
                'value' => 1
            ],
        ];
    }

    /**
     * This test should fail with php TypeError.
     *
     * @param mixed  $value
     * @dataProvider providerVerifyManyContractsInvalid
     */
    public function testVerifyManyContractsInvalid($value): void
    {
        $this->expectException(ContractViolation::class);
        $this->stub->sub(1);
    }
}
