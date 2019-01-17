<?php

namespace PhpDeal\Functional\Inherit;

use PHPUnit\Framework\TestCase;

class ContractTest extends TestCase
{
    /** @var StubWithoutInherit */
    private $stubWithoutInherit;
    /** @var StubWithInherit */
    private $stubWithInherit;

    public function setUp()
    {
        $this->stubWithoutInherit = new StubWithoutInherit();
        $this->stubWithInherit = new StubWithInherit();
    }

    public function tearDown()
    {
        unset($this->stubWithoutInherit, $this->stubWithInherit);
    }

    public function testWithoutInheritIsValid()
    {
        $this->stubWithoutInherit->add("test");
        $this->stubWithoutInherit->add(0);
        $this->assertEquals(10, $this->stubWithoutInherit->getAmount());
    }

    /**
     * @expectedException \PhpDeal\Exception\ContractViolation
     * @return void
     */
    public function testWithInheritInvalid()
    {
        $this->stubWithInherit->add(0);
    }

    public function testWithInheritValid()
    {
        $this->stubWithInherit->add(10);
        $this->assertEquals(10, $this->stubWithInherit->getAmount());
    }
}
