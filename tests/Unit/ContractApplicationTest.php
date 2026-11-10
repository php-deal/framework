<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit;

use Closure;
use Go\Core\AspectContainer;
use PhpDeal\Aspect\InvariantCheckerAspect;
use PhpDeal\Aspect\PostconditionCheckerAspect;
use PhpDeal\Aspect\PreconditionCheckerAspect;
use PhpDeal\ContractApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContractApplication::class)]
final class ContractApplicationTest extends TestCase
{
    public function testRegistersEveryContractAspect(): void
    {
        $container = $this->createMock(AspectContainer::class);
        $factories = [];
        $container
            ->expects(self::exactly(3))
            ->method('addLazyService')
            ->willReturnCallback(static function (string $id, Closure $factory) use (&$factories): void {
                $factories[$id] = $factory;
            });

        ContractApplication::registerAspects($container);

        self::assertSame(
            [PreconditionCheckerAspect::class, PostconditionCheckerAspect::class, InvariantCheckerAspect::class],
            \array_keys($factories),
        );
        self::assertInstanceOf(PreconditionCheckerAspect::class, $factories[PreconditionCheckerAspect::class]($container));
        self::assertInstanceOf(PostconditionCheckerAspect::class, $factories[PostconditionCheckerAspect::class]($container));
        self::assertInstanceOf(InvariantCheckerAspect::class, $factories[InvariantCheckerAspect::class]($container));
    }

    public function testKernelIsRunning(): void
    {
        $container = ContractApplication::getInstance()->getContainer();

        self::assertTrue($container->has(PreconditionCheckerAspect::class));
        self::assertTrue($container->has(PostconditionCheckerAspect::class));
        self::assertTrue($container->has(InvariantCheckerAspect::class));
    }
}
