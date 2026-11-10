<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/demo'])
    ->exclude(['cache'])
    ->append([__FILE__]);

return new Config()
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2x0'                  => true,
        '@PER-CS2x0:risky'            => true,
        'declare_strict_types'        => true,
        'native_function_invocation'  => ['include' => ['@all'], 'scope' => 'all', 'strict' => true],
        'no_unused_imports'           => true,
        'ordered_imports'             => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'global_namespace_import'     => ['import_classes' => true, 'import_constants' => false, 'import_functions' => false],
        'phpdoc_align'                => ['align' => 'vertical'],
        'phpdoc_trim'                 => true,
        'single_quote'                => true,
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'match', 'parameters']],
        'binary_operator_spaces'      => ['default' => 'single_space', 'operators' => ['=>' => 'align_single_space_minimal', '=' => 'align_single_space_minimal']],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
