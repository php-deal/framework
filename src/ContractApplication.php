<?php
/**
 * PHP Deal framework
 *
 * @copyright Copyright 2014, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpDeal;

use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use PhpDeal\Aspect\ContractCheckerAspect;

/**
 * Main kernel class for enabling "design by contract" paradigm
 */
class ContractApplication extends AspectKernel
{

    /**
     * Configures an AspectContainer with advisors, aspects and pointcuts
     *
     * @param AspectContainer $container
     *
     * @return void
     */
    protected function configureAop(AspectContainer $container)
    {
        $reader = $container->get('aspect.annotation.reader');
        $container->registerAspect(new ContractCheckerAspect($reader));
    }
}
