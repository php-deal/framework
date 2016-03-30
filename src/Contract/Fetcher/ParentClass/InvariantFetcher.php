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

class InvariantFetcher extends Fetcher
{
    /**
     * @param ReflectionClass $class
     * @param Reader $reader
     * @param array $contracts
     * @return array
     */
    public function getConditions(ReflectionClass $class, Reader $reader, array $contracts = [])
    {
        $parentClass = $class->getParentClass();

        if (!$parentClass) {
            return $contracts;
        }

        $annotations = $reader->getClassAnnotations($parentClass);
        $contractAnnotations = $this->getContractAnnotations($annotations);
        $contracts = array_merge($contracts, $contractAnnotations);

        return $this->getConditions($parentClass, $reader, $contracts);
    }
} 
