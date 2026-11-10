<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Exception;

use PhpDeal\Exception\ContractViolation;
use PhpDeal\Exception\InvalidContractException;
use PhpDeal\Exception\InvariantViolation;
use PhpDeal\Exception\PostconditionViolation;
use PhpDeal\Exception\PreconditionViolation;
use PhpDeal\Tests\Unit\Stub\FakeInvocation;
use PhpDeal\Tests\Unit\Stub\Square;
use PhpDeal\Tests\Unit\Stub\Status;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use stdClass;

#[CoversClass(ContractViolation::class)]
#[CoversClass(PreconditionViolation::class)]
#[CoversClass(PostconditionViolation::class)]
#[CoversClass(InvariantViolation::class)]
#[CoversClass(InvalidContractException::class)]
final class ContractViolationTest extends TestCase
{
    public function testMessageDescribesTheCall(): void
    {
        $violation = new PreconditionViolation(FakeInvocation::on(new Square(), 'scale', 13.0), '$factor !== 13.0');

        self::assertSame(
            'Precondition "$factor !== 13.0" violated in ' . Square::class . '->scale(13.0)',
            $violation->getMessage(),
        );
        self::assertSame('$factor !== 13.0', $violation->contract);
        self::assertSame(Square::class, $violation->className);
        self::assertSame('scale', $violation->methodName);
    }

    public function testStaticCallsUseDoubleColon(): void
    {
        $violation = new PostconditionViolation(FakeInvocation::onStatic(Square::class, 'create'), 'false');

        self::assertSame('Postcondition "false" violated in ' . Square::class . '::create()', $violation->getMessage());
    }

    public function testMessageIncludesThePreviousError(): void
    {
        $violation = new InvariantViolation(
            FakeInvocation::on(new Square(), 'scale', 1.0),
            'check()',
            new RuntimeException('Value is too small'),
        );

        self::assertStringEndsWith('->scale(1.0): Value is too small', $violation->getMessage());
        self::assertStringStartsWith('Invariant "check()"', $violation->getMessage());
    }

    public function testBaseViolationKeepsGenericKind(): void
    {
        $violation = new ContractViolation(FakeInvocation::on(new Square(), 'scale', 1.0), 'false');

        self::assertStringStartsWith('Contract "false" violated', $violation->getMessage());
    }

    public function testLocationPointsToTheMethod(): void
    {
        $method    = new ReflectionMethod(Square::class, 'scale');
        $violation = new PreconditionViolation(FakeInvocation::on(new Square(), 'scale', 1.0), 'false');

        self::assertSame($method->getFileName(), $violation->getFile());
        self::assertSame($method->getStartLine(), $violation->getLine());
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function providerArguments(): iterable
    {
        yield 'null' => [null, 'null'];
        yield 'true' => [true, 'true'];
        yield 'false' => [false, 'false'];
        yield 'integer' => [42, '42'];
        yield 'float' => [1.5, '1.5'];
        yield 'string' => ["it's", "'it\\'s'"];
        yield 'long string' => [\str_repeat('a', 100), "'" . \str_repeat('a', 36) . '...'];
        yield 'long unicode string' => [\str_repeat('я', 50), "'" . \str_repeat('я', 36) . '...'];
        yield 'array' => [[1, 2, 3], 'array(3)'];
        yield 'enum' => [Status::Active, Status::class . '::Active'];
        yield 'object' => [new stdClass(), 'stdClass'];
        yield 'closure' => [static fn() => null, 'Closure'];
    }

    #[DataProvider('providerArguments')]
    public function testArgumentsAreRenderedSafely(mixed $argument, string $expected): void
    {
        $invocation = new FakeInvocation(new Square(), Square::class, 'scale', [$argument]);
        $violation  = new PreconditionViolation($invocation, 'false');

        self::assertStringEndsWith('->scale(' . $expected . ')', $violation->getMessage());
    }

    public function testResourcesAreRenderedByType(): void
    {
        $resource = \fopen('php://memory', 'rb');
        self::assertIsResource($resource);

        $violation = new PreconditionViolation(new FakeInvocation(new Square(), Square::class, 'scale', [$resource]), 'false');
        \fclose($resource);

        self::assertStringEndsWith('->scale(resource (stream))', $violation->getMessage());
    }
}
