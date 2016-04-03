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

class MethodConditionWithInheritDocFetcher extends AbstractFetcher
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
        while (
            preg_match('/\@inheritdoc/i', $class->getMethod($methodName)->getDocComment())
            && ($class = $class->getParentClass())
            && $class->hasMethod($methodName)
        ) {
            $parentMethods[] = $class->getMethod($methodName);
        }

        foreach ($parentMethods as $parentMethod) {
            $annotations = array_merge($annotations, $this->annotationReader->getMethodAnnotations($parentMethod));
        }
        $contracts = $this->filterContractAnnotation($annotations);

        return $contracts;
    }
}
