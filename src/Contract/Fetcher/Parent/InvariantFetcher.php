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
        $parents = [];

        $this->getParentClasses($class, $parents);
        $this->getInterfaces($class, $parents);

        foreach ($parents as $parent) {
            $annotations = array_merge($annotations, $this->annotationReader->getClassAnnotations($parent));
        }
        $contracts = $this->filterContractAnnotation($annotations);

        return $contracts;
    }

    /**
     * @param ReflectionClass $class
     * @param array $parents
     */
    private function getParentClasses(ReflectionClass $class, &$parents)
    {
        while ($class = $class->getParentClass()) {
            $parents[] = $class;
        }
    }

    /**
     * @param ReflectionClass $class
     * @param array $parents
     */
    private function getInterfaces(ReflectionClass $class, &$parents)
    {
        $interfaces = $class->getInterfaces();

        foreach ($interfaces as $interface) {
            $parents[] = $interface;
        }
    }
}
