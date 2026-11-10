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

namespace PhpDeal;

use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use PhpDeal\Aspect\InvariantCheckerAspect;
use PhpDeal\Aspect\PostconditionCheckerAspect;
use PhpDeal\Aspect\PreconditionCheckerAspect;
use PhpDeal\Contract\ContractEvaluator;
use PhpDeal\Contract\ContractFetcher;
use PhpDeal\Contract\ContractRewriter;

/**
 * Aspect kernel that enables Design by Contract checks
 *
 * Initialize it once, as early as possible in the front controller:
 *
 *     ContractApplication::getInstance()->init([
 *         'debug'        => true,
 *         'appDir'       => __DIR__ . '/..',
 *         'cacheDir'     => __DIR__ . '/../var/cache/contracts',
 *         'includePaths' => [__DIR__ . '/../src'],
 *     ]);
 *
 * Applications that already have a Go! AOP kernel call {@see registerAspects()} from it instead.
 */
class ContractApplication extends AspectKernel
{
    /**
     * Registers the contract aspects in an aspect container
     *
     * On PHP 8.5+ it also registers {@see ContractRewriter}, which compiles the contract expressions into the woven
     * classes. Call it from `configureAop()` of your own aspect kernel to combine contracts with other aspects.
     */
    public static function registerAspects(AspectContainer $container): void
    {
        $fetcher   = new ContractFetcher();
        $evaluator = new ContractEvaluator();

        $container->addLazyService(
            PreconditionCheckerAspect::class,
            static fn(): PreconditionCheckerAspect => new PreconditionCheckerAspect($fetcher, $evaluator),
        );
        $container->addLazyService(
            PostconditionCheckerAspect::class,
            static fn(): PostconditionCheckerAspect => new PostconditionCheckerAspect($fetcher, $evaluator),
        );
        $container->addLazyService(
            InvariantCheckerAspect::class,
            static fn(): InvariantCheckerAspect => new InvariantCheckerAspect($fetcher, $evaluator),
        );
        // Closures in attributes need PHP 8.5, older versions evaluate the expressions with eval()
        if (\PHP_VERSION_ID >= 80500) {
            $container->addLazyService(ContractRewriter::class, static fn(): ContractRewriter => new ContractRewriter());
        }
    }

    protected function configureAop(AspectContainer $container): void
    {
        self::registerAspects($container);
    }
}
