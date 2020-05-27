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

namespace PhpDeal\Functional\Verify\ClassPropagation;

use PhpDeal\Annotation as Contract;

class StubGrandparent
{
    /**
     * @param int $variable
     * @Contract\Verify("$variable !== 3")
     */
    public function add(int $variable): void
    {
    }
}
