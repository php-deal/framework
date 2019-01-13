<?php
declare(strict_types=1);

/**
 * PHP Deal framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpDeal;

use Doctrine\Common\Annotations\AnnotationReader;
use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use PhpDeal\Aspect\InheritCheckerAspect;
use Go\Core\GoAspectContainer;
use PhpDeal\Aspect\InvariantCheckerAspect;
use PhpDeal\Aspect\PostconditionCheckerAspect;
use PhpDeal\Aspect\PreconditionCheckerAspect;

/**
 * Main kernel class for enabling "design by contract" paradigm
 */
class ContractApplication extends AspectKernel
{

    /**
     * Configures an AspectContainer with advisors, aspects and pointcuts
     *
     * @param AspectContainer&GoAspectContainer $container
     *
     * @return void
     */
    protected function configureAop(AspectContainer $container): void
    {
        /** @var AnnotationReader $reader */
        $reader = $container->get('aspect.annotation.reader');

        $container->registerAspect(new InvariantCheckerAspect($reader));
        $container->registerAspect(new PostconditionCheckerAspect($reader));
        $container->registerAspect(new PreconditionCheckerAspect($reader));
        $container->registerAspect(new InheritCheckerAspect($reader));
    }
}
