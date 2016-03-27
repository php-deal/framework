<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Aspect;

use Go\Aop\Intercept\MethodInvocation;

class MethodArgumentsFetcher
{
    /**
     * Returns an associative list of arguments for the method invocation
     *
     * @param MethodInvocation $invocation
     * @return array
     */
    public function fetch(MethodInvocation $invocation)
    {
        $parameters    = $invocation->getMethod()->getParameters();
        $argumentNames = array_map(function (\ReflectionParameter $parameter) {
            return $parameter->name;
        }, $parameters);
        $parameters    = array_combine($argumentNames, $invocation->getArguments());

        return $parameters;
    }
} 
