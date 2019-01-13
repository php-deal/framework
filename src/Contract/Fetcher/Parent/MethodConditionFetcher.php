<?php
declare(strict_types=1);

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
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
     * @param string          $methodName
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getConditions(ReflectionClass $class, string $methodName): array
    {
        $annotations = [];
        $parentMethods = [];

        $this->getParentClassesMethods($class, $methodName, $parentMethods);
        $this->getInterfacesMethods($class, $methodName, $parentMethods);

        foreach ($parentMethods as $parentMethod) {
            $annotations[] = $this->annotationReader->getMethodAnnotations($parentMethod);
        }

        if (\count($annotations) > 0) {
            $annotations = \array_merge(...$annotations);
        }

        return $this->filterContractAnnotation($annotations);
    }

    /**
     * @param ReflectionClass $class
     * @param string          $methodName
     * @param array           $parentMethods
     * @throws \ReflectionException
     */
    private function getParentClassesMethods(ReflectionClass $class, string $methodName, array &$parentMethods): void
    {
        while (($class = $class->getParentClass()) && $class->hasMethod($methodName)) {
            $parentMethods[] = $class->getMethod($methodName);
        }
    }

    /**
     * @param ReflectionClass $class
     * @param string          $methodName
     * @param array           $parentMethods
     * @throws \ReflectionException
     */
    private function getInterfacesMethods(ReflectionClass $class, $methodName, &$parentMethods): void
    {
        foreach ($class->getInterfaces() as $interface) {
            if ($interface->hasMethod($methodName)) {
                $parentMethods[] = $interface->getMethod($methodName);
            }
        }
    }
}
