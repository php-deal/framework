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

namespace PhpDeal\Functional\Invariant\ClassPropagation;

use PhpDeal\Annotation as Contract;

/**
 * @Contract\Invariant("$this->variable !== 1")
 */
class Stub extends StubParent
{
    /**
     * @param int $variable
     */
    public function setVariable(int $variable): void
    {
        $this->variable = $variable;
    }
}
