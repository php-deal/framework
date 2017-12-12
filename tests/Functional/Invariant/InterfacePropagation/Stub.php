<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Functional\Invariant\InterfacePropagation;

use PhpDeal\Annotation as Contract;

/**
 * @Contract\Invariant("$this->variable != 1")
 */
class Stub implements StubInterfaceA, StubInterfaceB
{
    /**
     * @var int
     */
    public $variable;

    /**
     * @param int $variable
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;
    }
}
