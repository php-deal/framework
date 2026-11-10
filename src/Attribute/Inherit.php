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

use Attribute;

/**
 * Inherits the contracts of parent classes and interfaces without declaring own ones
 *
 * On a method it enables the pre- and post-conditions of the same method in every parent class and interface.
 * On a class it does the same for every public method, and enables the invariants of the parents.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final readonly class Inherit {}
