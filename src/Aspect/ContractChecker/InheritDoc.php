<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Aspect\ContractChecker;

use ReflectionMethod;

class InheritDoc
{
    /**
     * @param ReflectionMethod $method
     * @return bool
     */
    public function hasInheritDoc(ReflectionMethod $method)
    {
        return preg_match('/\@inheritdoc/i', $method->getDocComment()) > 0;
    }
} 
