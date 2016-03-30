<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Contract\Fetcher\ParentClass;

use Doctrine\Common\Annotations\Reader;
use ReflectionClass;

class MethodConditionFetcher extends Fetcher
{
    /**
     * @param ReflectionClass $class
     * @param Reader $reader
     * @param string $methodName
     * @param array $contracts
     * @return array
     */
    public function getConditions(ReflectionClass $class, Reader $reader, $methodName, array $contracts = [])
    {
        $parentClass = $class->getParentClass();

        if (!$parentClass) {
            return $contracts;
        }

        $parentMethod = $parentClass->getMethod($methodName);
        $annotations = $reader->getMethodAnnotations($parentMethod);
        $contractAnnotations = $this->getContractAnnotations($annotations);
        $contracts = array_merge($contracts, $contractAnnotations);

        return $this->getConditions($parentClass, $reader, $methodName, $contracts);
    }
}
