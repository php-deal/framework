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

use Closure;
use PhpDeal\Attribute\Contract;
use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;

/**
 * A contract together with the class scope its expression is evaluated in
 *
 * Contracts of a class are evaluated in the scope of that class, so they can read its private members even when the
 * method is called on a subclass. Contracts of an interface are evaluated in the scope of the implementing class.
 */
final readonly class BoundContract
{
    /**
     * Compiled expression bound to the scope, null when the expression is evaluated with eval()
     */
    public ?Closure $check;

    /**
     * @param Contract     $contract Contract attribute
     * @param class-string $scope    Class scope for the expression
     */
    public function __construct(
        public Contract $contract,
        public string $scope,
    ) {
        // Contract implementations of other packages are always evaluated with eval()
        $isCompilable = $contract instanceof Verify || $contract instanceof Ensure || $contract instanceof Invariant;
        $check        = $isCompilable ? $contract->check : null;
        // The closure is declared in the woven trait or the interface, it must run in the scope of the contract
        $this->check = $check !== null ? Closure::bind($check, null, $scope) : null;
    }
}
