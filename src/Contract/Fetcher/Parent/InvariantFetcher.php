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

class InvariantFetcher extends AbstractFetcher
{
    /**
     * Fetches conditions from all parent classes recursively
     *
     * @param ReflectionClass $class
     *
     * @return array
     */
    public function getConditions(ReflectionClass $class)
    {
        $annotations   = [];
        $parentClasses = [];

        $this->getParentClass($class, $parentClasses);
        $this->getInterfaces($class, $parentClasses);

        foreach ($parentClasses as $parentClass) {
            $annotations = array_merge($annotations, $this->annotationReader->getClassAnnotations($parentClass));
        }
        $contracts = $this->filterContractAnnotation($annotations);

        return $contracts;
    }

    /**
     * @param ReflectionClass $class
     * @param array $parentClasses
     */
    private function getParentClass(ReflectionClass $class, &$parentClasses)
    {
        while ($class = $class->getParentClass()) {
            $parentClasses[] = $class;
        }
    }

    /**
     * @param ReflectionClass $class
     * @param array $parentClasses
     */
    private function getInterfaces(ReflectionClass $class, &$parentClasses)
    {
        $interfaces = $class->getInterfaces();

        foreach ($interfaces as $interface) {
            $parentClasses[] = $interface;
        }
    }
}
