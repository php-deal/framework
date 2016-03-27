<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Aspect\Fetcher;

use Doctrine\Common\Annotations\Reader;
use ReflectionClass;

class ParentsContractsFetcher
{
    /**
     * @var string
     */
    private $expectedAnnotationType;

    /**
     * @param string $expectedAnnotationType
     */
    public function __construct($expectedAnnotationType)
    {
        $this->expectedAnnotationType = $expectedAnnotationType;
    }

    public function getParentsContracts(ReflectionClass $class, Reader $reader, array $contracts, $methodName)
    {
        $parentClass = $class->getParentClass();

        if (!$parentClass) {
            return $contracts;
        }

        $parentMethod = $parentClass->getMethod($methodName);
        $annotations = $reader->getMethodAnnotations($parentMethod);
        $contractAnnotations = $this->getContractAnnotations($annotations);
        $contracts = array_merge($contracts, $contractAnnotations);

        return $this->getParentsContracts($parentClass, $reader, $contracts, $methodName);
    }

    private function getContractAnnotations(array $annotations)
    {
        $contractAnnotations = [];

        foreach ($annotations as $annotation) {
            if (is_a($annotation, $this->expectedAnnotationType)) {
                $contractAnnotations[] = $annotation;
            }
        }

        return $contractAnnotations;
    }
} 
