<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpDeal\Annotation;

use Doctrine\Common\Annotations\Annotation as BaseAnnotation;

/**
 * This annotation defines a Post-condition check, applied to the method
 *
 * @Annotation
 * @Target("METHOD")
 */
class Ensure extends BaseAnnotation
{
    public function __toString()
    {
        return $this->value;
    }
}
