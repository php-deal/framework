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

namespace PhpDeal\Contract;

use PhpDeal\Attribute\Contract;

/**
 * A contract together with the class scope its expression is evaluated in
 *
 * Contracts of a class are evaluated in the scope of that class, so they can read its private members even when the
 * method is called on a subclass. Contracts of an interface are evaluated in the scope of the implementing class.
 */
final readonly class BoundContract
{
    /**
     * @param Contract     $contract Contract attribute
     * @param class-string $scope    Class scope for the expression
     */
    public function __construct(
        public Contract $contract,
        public string $scope,
    ) {}
}
