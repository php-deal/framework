<?php

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014-2026, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace PhpDeal\Attribute;

/**
 * Common interface of every contract attribute that carries a PHP expression
 *
 * The expression is evaluated in the scope of the class that declares the method, so it can read private and
 * protected members through `$this` or `self::`. Method arguments are available by their names.
 */
interface Contract
{
    /**
     * PHP expression that must evaluate to `true` (or `null`, for assertion libraries) for the contract to hold
     */
    public string $expression { get; }
}
