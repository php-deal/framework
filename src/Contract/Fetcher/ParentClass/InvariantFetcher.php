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
        while ($class = $class->getParentClass()) {
            $parentClasses[] = $class;
        }

        foreach ($parentClasses as $parentClass) {
            $annotations = array_merge($annotations, $this->annotationReader->getClassAnnotations($parentClass));
        }
        $contracts = $this->filterContractAnnotation($annotations);

        return $contracts;
    }
}
