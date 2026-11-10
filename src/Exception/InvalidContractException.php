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

namespace PhpDeal\Exception;

use DomainException;
use Throwable;

/**
 * Thrown when a contract is written incorrectly: its expression can not be compiled, or it returns
 * something else than a boolean or null
 *
 * This is a bug in the contract itself, not a violation of it.
 */
final class InvalidContractException extends DomainException implements ExceptionInterface
{
    /**
     * @param string $location Method that declares the contract, e.g. "App\Account::deposit()"
     */
    public static function forSyntaxError(string $expression, string $location, Throwable $previous): self
    {
        return new self(
            \sprintf('Contract "%s" of %s can not be compiled: %s', $expression, $location, $previous->getMessage()),
            0,
            $previous,
        );
    }

    /**
     * @param string $location Method that declares the contract, e.g. "App\Account::deposit()"
     */
    public static function forInvalidResult(string $expression, string $location, mixed $result): self
    {
        return new self(\sprintf(
            'Contract "%s" of %s must return a boolean or null (for assertions), %s returned',
            $expression,
            $location,
            \get_debug_type($result),
        ));
    }

    /**
     * @param string $location Method that declares the contract, e.g. "App\Account::deposit()"
     */
    public static function forStaticContext(string $expression, string $location): self
    {
        return new self(\sprintf('Contract "%s" of %s uses $this, but the method is static', $expression, $location));
    }
}
