<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Fixtures\Verify;

use Assert\Assertion;
use PhpDeal\Attribute\Verify;

class Account
{
    private int $limit = 42;

    public static int $fee = 5;

    #[Verify('\is_numeric($amount)')]
    public function numeric(float $amount): float
    {
        return $amount;
    }

    #[Verify('$amount > $this->limit')]
    public function aboveLimit(int $amount): void {}

    #[Verify('Assert\Assertion::greaterThan($amount, 5)')]
    public function withAssertion(int $amount): bool
    {
        return Assertion::integer($amount);
    }

    #[Verify('$amount < 10')]
    #[Verify('$amount > 5')]
    public function between(int $amount): void {}

    #[Verify('$currency === "EUR"')]
    public function withDefault(int $amount, string $currency = 'EUR'): void {}

    #[Verify('\count($amounts) > 0 && \min($amounts) > 0')]
    public function variadic(int ...$amounts): void {}

    #[Verify('$amount > self::$fee')]
    public static function staticMethod(int $amount): void {}

    #[Verify('$amount > $this->limit')]
    public static function staticWithThis(int $amount): void {}

    #[Verify('$amount >')]
    public function syntaxError(int $amount): void {}

    #[Verify('$amount')]
    public function notBoolean(int $amount): void {}

    #[Verify('$this->undefinedMethod()')]
    public function brokenExpression(): void {}

    #[Verify('$amount > 0 // must be positive')]
    public function withComment(int $amount): void {}

    #[Verify('$this->isAllowed($amount)')]
    public function guarded(int $amount): void {}

    /**
     * Used only by a contract: the PHPStan extension of php-deal must not report it as unused
     */
    private function isAllowed(int $amount): bool
    {
        return $amount % 2 === 0;
    }

    #[Verify('$name !== \'$this\'')]
    public static function staticWithThisInString(string $name): void {}
}
