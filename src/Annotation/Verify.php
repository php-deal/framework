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

namespace PhpDeal\Annotation;

use Doctrine\Common\Annotations\Annotation as BaseAnnotation;

/**
 * This annotation defines a Pre-condition check, applied to the method
 *
 * @Annotation
 * @Target("METHOD")
 */
class Verify extends BaseAnnotation
{
    public function __toString()
    {
        return $this->value;
    }
}
