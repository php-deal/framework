<?php
namespace Demo;

use PhpDeal\Annotation as Contract;
/**
 * Simple trade account class
 * @Contract\Invariant("$this->balance >= 0")
 */
class Account extends Account__AopProxied implements \Go\Aop\Proxy
{

    /**
     * Property was created automatically, do not change it manually
     */
    private static $__joinPoints = [
        'method' => [
            'deposit' => [
                'advisor.PhpDeal\\Aspect\\PreconditionCheckerAspect->preConditionContract',
                'advisor.PhpDeal\\Aspect\\InvariantCheckerAspect->invariantContract',
                'advisor.PhpDeal\\Aspect\\PostconditionCheckerAspect->postConditionContract'
            ],
            'withdraw' => [
                'advisor.PhpDeal\\Aspect\\InvariantCheckerAspect->invariantContract',
                'advisor.PhpDeal\\Aspect\\InheritCheckerAspect->inheritMethodContracts'
            ],
            'getBalance' => [
                'advisor.PhpDeal\\Aspect\\InvariantCheckerAspect->invariantContract',
                'advisor.PhpDeal\\Aspect\\PostconditionCheckerAspect->postConditionContract'
            ]
        ]
    ];
    
    /**
     * Deposits fixed amount of money to the account
     *
     * @param float $amount
     *
     * @Contract\Verify("$amount>0 && is_numeric($amount)")
     * @Contract\Ensure("$this->balance == $__old->balance+$amount")
     */
    public function deposit($amount)
    {
        return self::$__joinPoints['method:deposit']->__invoke($this, [$amount]);
    }
    
    /**
     * @Contract\Inherit()
     * @param float $amount
     */
    public function withdraw($amount)
    {
        return self::$__joinPoints['method:withdraw']->__invoke($this, [$amount]);
    }
    
    /**
     * Returns current balance
     *
     * @Contract\Ensure("$__result == $this->balance")
     *
     * @return float
     */
    public function getBalance()
    {
        return self::$__joinPoints['method:getBalance']->__invoke($this);
    }
    
}
\Go\Proxy\ClassProxy::injectJoinPoints(Account::class);