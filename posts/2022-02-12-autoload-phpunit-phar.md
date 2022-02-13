@metadata
layout: post
title: Autoload PHPUnit phar
date: 2022-02-12
canonical: 2022-02-12-autoload-phpunit-phar.html
excerpt: How to let PHPStan's autoloader access PHPUnit when the using the PHPUnit phar?
@endmetadata

For some time now, instead of installing PHPUnit using composer
or [symfony/phpunit-bridge](https://symfony.com/doc/current/components/phpunit_bridge.html)
I'm trying to use its phar.

One of many reasons is because I'd like to decrease the number of downloaded dependency.

@html
<p>
I really like how PHPStan handles it.<br>
<code class="inline">composer require --dev phpstan/phpstan</code> will only download the phar so no extra dependencies.
When developing an extension you still have all the classes available and discoverable with PHPStorm or else etc..
It is just beautiful and a great pleasure to use PHPStan or develop extensions for it! 😍
</p>
@endhtml

So I am testing [PHIVE](https://phar.io/) to manage tools that don't have the same approach as PHPStan.
That's the case of PHPUnit.

An issue with this setup is that PHPStan cannot analyse the tests because it won't be able
to [discover PHPUnit's symbols](https://phpstan.org/user-guide/discovering-symbols).
If you search in PHPStan's issue queue, you may find some like
[Standalone PHPUnit PHAR and TestCase class not found errors](https://github.com/phpstan/phpstan/issues/2661).

@html
<p>
The way to resolve the issue at the time is no longer supported since <code class="inline">autoload_files</code>
has been removed in version
<a href="https://github.com/phpstan/phpstan/releases/tag/1.0.0">1.0.0</a>
</p>
@endhtml

@html
<p>
If we try the "new" <code class="inline">bootstrapFiles</code> configuration (it has been out for quite sometime now, so it is not that new 😛) like:
</p>
@endhtml

```yaml
parameters:
	bootstrapFiles:
	    - phar://%rootDir%/../../../tools/phpunit.phar
```

We have an error:

```shell
$ ./tools/phpstan
Note: Using configuration file /app/phpstan.neon.dist.
Bootstrap file phar:///app/vendor/phpstan/phpstan/../../../tools/phpunit.phar does not exist.
```


@html
<p>
So what changed between <code class="inline">autoload_files</code> and <code class="inline">bootstrapFiles</code>?
</p>
@endhtml

Not that much :P

@html
<p>
That's the <code class="inline">autoload_files</code> part
(see the <a href="https://github.com/phpstan/phpstan-src/commit/7a21246cae9dd7968bf7bef92223b53f5d681b72">commit</a>
that removed it):
</p>
@endhtml

```php
foreach ($autoloadFiles as $parameterAutoloadFile) {
	if (!file_exists($parameterAutoloadFile)) {
		$errorOutput->writeLineFormatted(sprintf('Autoload file %s does not exist.', $parameterAutoloadFile));
		throw new \PHPStan\Command\InceptionNotSuccessfulException();
	}
	(static function (string $file) use ($container): void {
		require_once $file;
	})($parameterAutoloadFile);
}
```

@html
<p>
And that's the <code class="inline">bootstrapFiles</code>
(see this <a href="https://github.com/phpstan/phpstan-src/blob/425dee71bf94bfea60afd42aea99377c198e283f/src/Command/CommandHelper.php#L477">file</a>):
</p>
@endhtml

```php
private static function executeBootstrapFile(
	string $file,
	Container $container,
	Output $errorOutput,
	bool $debugEnabled,
): void
{
	if (!is_file($file)) {
		$errorOutput->writeLineFormatted(sprintf('Bootstrap file %s does not exist.', $file));
		throw new InceptionNotSuccessfulException();
	}
	try {
		(static function (string $file) use ($container): void {
			require_once $file;
		})($file);
	} catch (Throwable $e) {
		$errorOutput->writeLineFormatted(sprintf('%s thrown in %s on line %d while loading bootstrap file %s: %s', get_class($e), $e->getFile(), $e->getLine(), $file, $e->getMessage()));

		if ($debugEnabled) {
			$errorOutput->writeLineFormatted($e->getTraceAsString());
		}

		throw new InceptionNotSuccessfulException();
	}
}
```

@html
<p>
The first one uses <code class="inline">file_exists</code> whereas the latter uses <code class="inline">is_file</code>.
</p>
@endhtml

If you isolate this part we'll see the difference. Let's execute this script:

```php
<?php

$path = 'phar://'.__DIR__.'/some.phar';

\var_dump(
    \file_exists($path),
    \is_file($path),
);
```

The result is:

```shell
$ php require-phar.php
bool(true)
bool(false)
```

At first, I didn't know if it was a bug in PHP or the expected behavior.
I've even opened an [issue](https://github.com/php/php-src/issues/8087) to have more information on it 😅

@html
<p>
After a little pause and some fresh air it became clearer when I thought about the fact that
<code class="inline">is_file</code> works great with file inside a phar:
</p>
@endhtml

```php
<?php

\is_file('phar://'.__DIR__.'/some.phar/file.php');
```

@html
<p>Ho! Wait! <code  class="inline">'phar://'.__DIR__.'/some.phar'</code> is considered a directory!</p>
@endhtml

@html
<p>
Ok so now that we know how PHPStan handle the <code class="inline">bootstrapFiles</code>,
how could we configure it to let its autoloader access PHPUnit?
</p>
<p>
Since <code class="inline">require_once</code> is used we could just directly use the phar file:
</p>
@endhtml

```yaml
parameters:
	bootstrapFiles:
	    - ./tools/phpunit.phar
```

@html
<p>
It works nevertheless <code class="inline">require_once</code> also execute the code.
</p>
@endhtml

Do we really want to execute a code that has not been designed to be required this way?

Let's take a look!

When requiring a phar like that:

```php
require_once __DIR__.'/phpunit.phar';
```

The [phar file stub](https://www.php.net/manual/en/phar.fileformat.stub.php) is the executed file.

The stub for PHPUnit phar looks like this
(it is the [PHP Autoload Builder](https://github.com/theseer/autoload) [template](https://github.com/sebastianbergmann/phpunit/blob/42839036a3392d124e12dea4c7b76fbf818123e0/build/templates/binary-phar-autoload.php.in)
used by PHPUnit):

```php
#!/usr/bin/env php
<?php
if (!version_compare(PHP_VERSION, PHP_VERSION, '=')) {
    fwrite(
        STDERR,
        sprintf(
            '%s declares an invalid value for PHP_VERSION.' . PHP_EOL .
            'This breaks fundamental functionality such as version_compare().' . PHP_EOL .
            'Please use a different PHP interpreter.' . PHP_EOL,

            PHP_BINARY
        )
    );

    die(1);
}

if (version_compare('7.3.0', PHP_VERSION, '>')) {
    fwrite(
        STDERR,
        sprintf(
            'PHPUnit X.Y.Z by Sebastian Bergmann and contributors.' . PHP_EOL . PHP_EOL .
            'This version of PHPUnit requires PHP >= 7.3.' . PHP_EOL .
            'You are using PHP %s (%s).' . PHP_EOL,
            PHP_VERSION,
            PHP_BINARY
        )
    );

    die(1);
}

foreach (['dom', 'json', 'libxml', 'mbstring', 'tokenizer', 'xml', 'xmlwriter'] as $extension) {
    if (extension_loaded($extension)) {
        continue;
    }

    fwrite(
        STDERR,
        sprintf(
            'PHPUnit requires the "%s" extension.' . PHP_EOL,
            $extension
        )
    );

    die(1);
}

if (__FILE__ === realpath($_SERVER['SCRIPT_NAME'])) {
    $execute = true;
} else {
    $execute = false;
}

$options = getopt('', array('prepend:', 'manifest'));

if (isset($options['prepend'])) {
    require $options['prepend'];
}

if (isset($options['manifest'])) {
    $printManifest = true;
}

unset($options);

define('__PHPUNIT_PHAR__', str_replace(DIRECTORY_SEPARATOR, '/', __FILE__));
define('__PHPUNIT_PHAR_ROOT__', 'phar://___PHAR___');

Phar::mapPhar('___PHAR___');

spl_autoload_register(
    function ($class) {
        static $classes = null;

        if ($classes === null) {
            $classes = [___CLASSLIST___];
        }

        if (isset($classes[$class])) {
            require_once 'phar://___PHAR___' . $classes[$class];
        }
    },
    ___EXCEPTION___,
    ___PREPEND___
);

foreach ([___CLASSLIST___] as $file) {
    require_once 'phar://___PHAR___' . $file;
}

require __PHPUNIT_PHAR_ROOT__ . '/phpunit/Framework/Assert/Functions.php';

if ($execute) {
    if (isset($printManifest)) {
        print file_get_contents(__PHPUNIT_PHAR_ROOT__ . '/manifest.txt');

        exit;
    }

    unset($execute);

    PHPUnit\TextUI\Command::main();
}

__HALT_COMPILER();
```

It starts with some check about PHPUnit requirements, contains the autoload part which interest us, the entry point to
execute PHPUnit command, and some extra options.

You could see that it will only execute PHPUnit when the phar is directly executed from command line thanks to:

```php
//..

if (__FILE__ === realpath($_SERVER['SCRIPT_NAME'])) {
    $execute = true;
} else {
    $execute = false;
}

// ...

if ($execute) {
    // ...
    PHPUnit\TextUI\Command::main();
}

//..
```

That's actually pretty nice! It already contains everything we need and want out of the box!

I also discover that another PHPUnit phar is available! This one is especially built to use PHPUnit as a library only!
It is absolutely awesome!
Take a look at the
[template of the phar stub file](https://github.com/sebastianbergmann/phpunit/blob/42839036a3392d124e12dea4c7b76fbf818123e0/build/templates/library-phar-autoload.php.in):

```php
<?php declare(strict_types=1);
define('__PHPUNIT_PHAR__', str_replace(DIRECTORY_SEPARATOR, '/', __FILE__));
define('__PHPUNIT_PHAR_ROOT__', 'phar://___PHAR___');

Phar::mapPhar('___PHAR___');

spl_autoload_register(
    function ($class) {
        static $classes = null;

        if ($classes === null) {
            $classes = [___CLASSLIST___];
        }

        if (isset($classes[$class])) {
            require_once 'phar://___PHAR___' . $classes[$class];
        }
    },
    ___EXCEPTION___,
    ___PREPEND___
);

foreach ([___CLASSLIST___] as $file) {
    require_once 'phar://___PHAR___' . $file;
}

require __PHPUNIT_PHAR_ROOT__ . '/phpunit/Framework/Assert/Functions.php';

__HALT_COMPILER();
```

It contains only the autoload part!

We could use this one if we want to make sure nothing else pollutes PHPStan analysis.

> PHPUnit phar is scoped. Namespaces are prefix with "PHPUnit" except for "phpspec/prophecy".
> If your autoload is free of PHPUnit and Prophecy you should be fine and have no collision at all!

## Conclusion (or TL;DR 😛)

If you have to autoload PHPUnit phar just require it!

Two PHPUnit phars are available: one to be used as a binary the other as a library.
Choose the one matching your needs!

Awesome work from PHPUnit maintainers!
