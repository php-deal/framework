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

abstract class AbstractFetcher
{
    /**
     * @var string
     */
    protected $expectedAnnotationType;

    /**
     * @var Reader
     */
    protected $annotationReader;

    /**
     * @param string $expectedAnnotationType
     * @param Reader $reader
     */
    public function __construct($expectedAnnotationType, Reader $reader)
    {
        $this->expectedAnnotationType = $expectedAnnotationType;
        $this->annotationReader       = $reader;
    }

    /**
     * Performs filtering of annotations by the requested class name
     *
     * @param array $annotations
     * @return array
     */
    protected function filterContractAnnotation(array $annotations)
    {
        $contractAnnotations = [];

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $this->expectedAnnotationType) {
                $contractAnnotations[] = $annotation;
            }
        }

        return $contractAnnotations;
    }
}
