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

class Fetcher
{
    /**
     * @var string
     */
    protected $expectedAnnotationType;

    /**
     * @param string $expectedAnnotationType
     */
    public function __construct($expectedAnnotationType)
    {
        $this->expectedAnnotationType = $expectedAnnotationType;
    }

    /**
     * @param array $annotations
     * @return array
     */
    protected function getContractAnnotations(array $annotations)
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
