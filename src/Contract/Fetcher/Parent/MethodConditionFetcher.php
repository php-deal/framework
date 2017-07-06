<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpDeal\Contract\Fetcher\Parent;

use ReflectionClass;

class MethodConditionFetcher extends AbstractFetcher
{
    /**
     * Fetches conditions from all parent method prototypes recursively
     *
     * @param ReflectionClass $class
     * @param string $methodName
     *
     * @return array
     */
    public function getConditions(ReflectionClass $class, $methodName)
    {
        $annotations   = [];
        $parentMethods = [];

        $this->getParentClassMethods($class, $methodName, $parentMethods);
        $this->getInterfacesMethods($class, $methodName, $parentMethods);

        foreach ($parentMethods as $parentMethod) {
            $annotations = array_merge($annotations, $this->annotationReader->getMethodAnnotations($parentMethod));
        }
        $contracts = $this->filterContractAnnotation($annotations);

        return $contracts;
    }

    /**
     * @param ReflectionClass $class
     * @param string $methodName
     * @param array $parentMethods
     */
    private function getParentClassMethods(ReflectionClass $class, $methodName, &$parentMethods)
    {
        while (($class = $class->getParentClass()) && $class->hasMethod($methodName)) {
            $parentMethods[] = $class->getMethod($methodName);
        }
    }

    /**
     * @param ReflectionClass $class
     * @param string $methodName
     * @param array $parentMethods
     */
    private function getInterfacesMethods(ReflectionClass $class, $methodName, &$parentMethods)
    {
        $interfaces = $class->getInterfaces();

        foreach ($interfaces as $interface) {
            if ($interface->hasMethod($methodName)) {
                $parentMethods[] = $interface->getMethod($methodName);
            }
        }
    }
}
