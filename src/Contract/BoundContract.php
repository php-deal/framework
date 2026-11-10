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
use PhpDeal\Attribute\Ensure;
use PhpDeal\Attribute\Invariant;
use PhpDeal\Attribute\Verify;

/**
 * A contract together with the class scope its expression is evaluated in
 *
 * Contracts of a class are evaluated in the scope of that class, so they can read its private members even when the
 * method is called on a subclass. Contracts of an interface are evaluated in the scope of the implementing class.
 *
 * Only the built-in attributes are accepted: their expressions are constant expressions written in the source, so
 * no computed text ever reaches eval().
 */
final readonly class BoundContract
{
    /**
     * Compiled expression bound to the scope, null when the expression is evaluated with eval()
     */
    public ?Closure $check;

    /**
     * Whether the expression reads `$__old`, so the object must be cloned before the call
     */
    public bool $usesOldState;

    /**
     * @param Verify|Ensure|Invariant $contract Contract attribute
     * @param class-string            $scope    Class scope for the expression
     */
    public function __construct(
        public Verify|Ensure|Invariant $contract,
        public string $scope,
    ) {
        // The closure is declared in the woven trait or the interface, it must run in the scope of the contract
        $this->check        = $contract->check !== null ? Closure::bind($contract->check, null, $scope) : null;
        $this->usesOldState = \str_contains($contract->expression, '$__old');
    }
}
