<?php

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace PhpDeal\Functional\Invariant;

use Go\Lang\Annotation\Aspect;
use PhpDeal\Annotation as Contract;

/**
 * Physic invariant - speed of subject, can not be greater than speed of light in vacuum
 *
 * @Contract\Invariant("$this->speed < 299792458 && $this->speed >= 0")
 * @Aspect("") // Just unused annotation
 */
class Stub
{
    private $speed = 0;

    /**
     * Accelerate
     *
     * @param float $acceleration
     * @param float   $time
     */
    public function accelerate(float $acceleration, float $time): void
    {
        $this->speed += ($acceleration * $time);
    }

    /**
     * Decelerate
     *
     * @param float $decelaration
     * @param float   $time
     */
    public function decelerate(float $decelaration, float $time): void
    {
        $this->speed -= ($decelaration * $time);
    }
}
