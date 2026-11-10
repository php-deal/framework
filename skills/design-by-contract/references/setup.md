# Project setup

Contracts are checked only in classes that Go! AOP weaves. A class is woven when it is autoloaded through Composer
after the kernel has started, and its file is inside `includePaths`.

## Install

```bash
composer require php-deal/framework
```

PHP 8.4 or newer is required. Ask the user before adding the dependency to a project.

## Start the kernel

Start the kernel once, right after the Composer autoloader, before any class with contracts is loaded:

```php
use PhpDeal\ContractApplication;

require __DIR__ . '/../vendor/autoload.php';

ContractApplication::getInstance()->init([
    'debug'        => true,                                  // re-weave changed files automatically
    'appDir'       => __DIR__ . '/..',                       // the directory that contains vendor/
    'cacheDir'     => __DIR__ . '/../var/cache/contracts',   // writable, add it to .gitignore
    'includePaths' => [__DIR__ . '/../src'],                 // directories with contracts, keep it narrow
]);
```

Typical places: `public/index.php`, `bin/console` and `tests/bootstrap.php` (set `bootstrap="tests/bootstrap.php"` in
`phpunit.xml.dist`). In production the kernel is usually skipped:

```php
if (($_SERVER['APP_ENV'] ?? 'prod') !== 'prod') {
    ContractApplication::getInstance()->init([/* ... */]);
}
```

## Projects that already use Go! AOP

Go! AOP runs one kernel per application. Register the aspects in the existing kernel instead:

```php
use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use PhpDeal\ContractApplication;

final class ApplicationAspectKernel extends AspectKernel
{
    protected function configureAop(AspectContainer $container): void
    {
        ContractApplication::registerAspects($container);
    }
}
```

## PHPStan

PhpDeal ships a PHPStan extension: private members used only by contracts are not reported as unused. With
`phpstan/extension-installer` it is enabled automatically, otherwise add it to `phpstan.neon`:

```neon
includes:
    - vendor/php-deal/framework/extension.neon
```

## Check that weaving works

Write one test that breaks a precondition and expects `PhpDeal\Exception\PreconditionViolation`. If no exception is
thrown, the class is not woven. Check the following:

- the kernel starts before the class is autoloaded (no `require` of the class file, no class loaded earlier in the
  bootstrap);
- the file is inside `includePaths` and not inside `excludePaths`;
- the cache is stale: delete `cacheDir` and run again.
