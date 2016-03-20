<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Stub;

use PhpDeal\Annotation as Contract;

/**
 * @author Piotr Dawidiuk
 */
class VerifyStubWithAssert
{
    /**
     * @param int $value
     * @return bool
     *
     * @Contract\Verify("\Assert\Assertion::integer($value)")
     */
    public function add($value)
    {
        return true;
    }
}
