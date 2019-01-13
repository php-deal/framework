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

class InvariantFetcher extends AbstractFetcher
{
    /**
     * Fetches conditions from all parent classes recursively
     *
     * @param ReflectionClass $class
     *
     * @return array
     */
    public function getConditions(ReflectionClass $class): array
    {
        $annotations   = [];
        $parents = [];

        $this->getParentClasses($class, $parents);
        $this->getInterfaces($class, $parents);

        foreach ($parents as $parent) {
            $annotations[] = $this->annotationReader->getClassAnnotations($parent);
        }

        if (\count($annotations)) {
            $annotations = \array_merge(...$annotations);
        }

        return $this->filterContractAnnotation($annotations);
    }

    /**
     * @param ReflectionClass $class
     * @param array $parents
     */
    private function getParentClasses(ReflectionClass $class, array &$parents): void
    {
        while ($class = $class->getParentClass()) {
            $parents[] = $class;
        }
    }

    /**
     * @param ReflectionClass $class
     * @param array $parents
     */
    private function getInterfaces(ReflectionClass $class, array &$parents): void
    {
        foreach ($class->getInterfaces() as $interface) {
            $parents[] = $interface;
        }
    }
}
