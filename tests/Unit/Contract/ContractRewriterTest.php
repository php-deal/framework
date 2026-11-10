<?php

declare(strict_types=1);

namespace PhpDeal\Tests\Unit\Contract;

use Go\Instrument\Transformer\StreamMetaData;
use Go\Instrument\Transformer\SyntaxTreeRewriter;
use Go\Instrument\Transformer\TransformerResult;
use PhpDeal\Contract\ContractCompiler;
use PhpDeal\Contract\ContractRewriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ContractRewriter::class)]
#[UsesClass(ContractCompiler::class)]
final class ContractRewriterTest extends TestCase
{
    private const string SOURCE = <<<'PHP'
        <?php

        namespace App;

        use PhpDeal\Attribute\Ensure;
        use PhpDeal\Attribute\Invariant;
        use PhpDeal\Attribute\Verify;
        use Other\Verify as OtherVerify;

        #[Invariant('$this->balance >= 0')]
        class Account
        {
            #[Verify('$amount > 0')]
            #[Ensure(
                '$this->balance === $__old->balance + $amount'
            )]
            public function deposit(float $amount): void {}

            #[Verify(expression: '$amount > 0')]
            #[\PhpDeal\Attribute\Verify('$amount >')]
            #[OtherVerify('$amount > 0')]
            #[Verify('$amount > 0', check: null)]
            public static function create(float $amount, float ...$rest): void {}
        }
        PHP;

    private string $file = '';

    protected function setUp(): void
    {
        $this->file = \sys_get_temp_dir() . '/php-deal-rewriter-' . \bin2hex(\random_bytes(4)) . '.php';
        \file_put_contents($this->file, self::SOURCE);
    }

    protected function tearDown(): void
    {
        if (\is_file($this->file)) {
            \unlink($this->file);
        }
    }

    public function testCompilesContractsOnTheirLines(): void
    {
        $metadata = $this->createMetadata();

        $result = new SyntaxTreeRewriter(new ContractRewriter())->transform($metadata);

        self::assertSame(TransformerResult::Transformed, $result);
        $lines = \explode("\n", $metadata->source);
        self::assertCount(\substr_count(self::SOURCE, "\n") + 1, $lines, 'Line numbers must not change');
        self::assertSame(
            "#[Invariant('\$this->balance >= 0', check: static function (\$__this, \$__result = null, ...\$__arguments)"
            . ' { return ($__this->balance >= 0); })]',
            $lines[9],
        );
        self::assertSame(
            "    #[Verify('\$amount > 0', check: static function (\$__this, \$amount, ...\$__arguments)"
            . ' { return ($amount > 0); })]',
            $lines[12],
        );
        self::assertSame(
            "        '\$this->balance === \$__old->balance + \$amount', check: static function (\$__this, \$amount,"
            . ' $__result = null, $__old = null, ...$__arguments) { return ($__this->balance === $__old->balance + $amount); }',
            $lines[14],
        );
        self::assertSame(
            "    #[Verify(expression: '\$amount > 0', check: static function (\$__this, \$amount, \$rest, ...\$__arguments)"
            . ' { return ($amount > 0); })]',
            $lines[18],
        );
        // A syntax error, an attribute of another library and a given closure are left alone
        self::assertSame("    #[\\PhpDeal\\Attribute\\Verify('\$amount >')]", $lines[19]);
        self::assertSame("    #[OtherVerify('\$amount > 0')]", $lines[20]);
        self::assertSame("    #[Verify('\$amount > 0', check: null)]", $lines[21]);
    }

    public function testSourceWithoutContractsIsNotTransformed(): void
    {
        \file_put_contents($this->file, "<?php\n\nnamespace PhpDeal\\Tests;\n\nclass Plain {}\n");

        $result = new SyntaxTreeRewriter(new ContractRewriter())->transform($this->createMetadata());

        self::assertSame(TransformerResult::Abstain, $result);
    }

    private function createMetadata(): StreamMetaData
    {
        $stream = \fopen($this->file, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Can not open ' . $this->file);
        }
        try {
            return new StreamMetaData($stream, (string) \file_get_contents($this->file));
        } finally {
            \fclose($stream);
        }
    }
}
