@metadata
layout: post
title: Creating a static site generator from scratch - useless but fun 😛
date: 2022-01-09
@endmetadata

![Wait.. What??](/assets/images/first/wait_what.jpeg)

Yes!.. You read it correctly 😅

It has been a long time since I wanted to start a blog. I have written articles in the past but never
published them for various reasons. One of them: I simply didn't have a place where I owned the content.

When I finally decided to do it, it just occured to me that it could be fun to try a little game!

![Wanna play a game?](/assets/images/first/wanna_play_a_game.png)

What if instead of using a well established static site generator I create one from scratch?

What if instead of using a framework, some components of a framework or miscellaneous libraries I did it using only bare PHP? (and of course HTML/CSS/JS but that's not the point)
And what if I did not have the right to look at them at all to avoid inspiration that become copy/paste?

What if it was time-boxed? Let's say 24h to do it! (just hours here and there not 24h straight)

You get the picture 😅 Doing it seems to be a terrible idea but honestly a very fun one!

![But.. Why?](/assets/images/first/but_why.png)

Why doing it? *Just for fun!*

## Disclaimer: could you use it?

**Please don't!** 😛

This blog post is not about selling a new shiny tool that you could use.
It's about the journey from having a stupid idea to realising it just for the fun of it.

This site has been generated with it, so it seems it is usable, nevertheless I would advise you to stay away from it.
I cannot guarantee it's future. The interface is.. minimalist I would say, and it has not been created to be used by anyone else than myself.

Now that's said let's continue!

## The process

> Do not assume this process/approach is the correct one. Please do otherwise.
>
> Remember that the resulting software has been developed in a limited time, for recreational purpose.
>
> Assume it is not well architectured, poorly coded etc.. etc..
>
> I definitely took some shortcuts
> (for instance I don't mind an exception to be thrown at the final user because I'm the only final user).
>
> Some parts are not detailed on purpose for the moment.

### The specifications

I started to write down what I wanted/needed. Here are the bare notes I wrote:

```markdown
# Specifications

Generate blog articles from a markdown like language (similar to Jekyll)

support:
* title: # => h1, ## => h2 (only # to define title)
* bold: **bold**
* italic: *italic*
* strikethrough: ~~strikethrough~~
* link:
  [I'm an inline-style link](https://www.google.com)
  [I'm an inline-style link with title](https://www.google.com "Google's Homepage")
* images: ![alt text](https://placekitten.com/200/300)
* horizontal rule: --- => hr
* code block: syntax highlighting (https://highlightjs.org/)
* list: ul (and ol?)
* blockquotes: >
* HTML

use metadata to describe it: something like https://jekyllrb.com/docs/front-matter/
Or just HTML comments?
Or just another custom way?

what kind of pages?
* homepage => list articles (do not forget pagination)
* about and other "solo" pages
* article page

usage
static-generator generate --config config.php --src sources/ --dist build/

configuration by file or by command line options
cli options supersede file config

Could be nice to have:
* watcher
```

Using a syntax similar to Markdown was important to minimize the migration to "real" static site generator like
[Jekyll](https://jekyllrb.com/).

As you could see, from the beginning I accepted one dependency: [highlightjs](https://highlightjs.org/).
Highlighting code is very important for futur articles.
Developing it in PHP is possible, maybe I will try to do it for fun one day but for the moment we have enough ~~work~~ fun to do!
I don't consider the "no dependency" rule broken mainly because I didn't want to depend on a PHP package,
and it is a JS package 😛.

### No PHP package dependency

I am neither allowed to depend on a PHP package nor to take a look at it.

For this first challenge, I'm going to allow only dev tools. The chosen ones were:

@html
<ul>
    <li><a href="https://phpunit.de/">PHPUnit</a> for the tests</li>
    <li><a href="https://psalm.dev/">Psalm</a> for the static analysis tool (I'm used to <a href="https://phpstan.org/">PHPStan</a> so it will change me)</li>
    <li><a href="https://github.com/FriendsOfPHP/PHP-CS-Fixer">PHP Coding Standards Fixer</a> for the linter</li>
    <li><a href="https://infection.github.io/">Infection</a> for mutation testing</li>
</ul>
@endhtml

To install all that I used [PHIVE](https://phar.io/).

> Composer is installed because Psalm seems to need its autoloader.
> For the moment I did not dig why or if it is configurable.

What implies not using [Composer](https://getcomposer.org/)?
Do the autoloading ourselves 😛

Implementing [spl_autoload_register](https://www.php.net/manual/en/function.spl-autoload-register.php)
is really simple and straightforward since I don't have any dependency.

```php
<?php

declare(strict_types=1);

\spl_autoload_register(function (string $class): void {
    $classFile = \str_replace(
        ['beram\\PiggyStatic\\', '\\'],
        ['', '/'],
        $class
    );
    $path = __DIR__ . '/src/' . $classFile . '.php';
    if (\file_exists($path)) {
        include $path;
    }
});
```

And kind of the same for the [tests' autoload](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/tests/autoload.php).

### Markdawn: an ersatz of Markdown syntax

Markdown syntax is complex.
A lot of different variants exist: the original, the GitHub Flavored Markdown, Markdown Extra, MultiMarkdown etc..

To ease the development I choose to create my own markup language based on Markdown but with some adjustments.

That's how **Markdawn** is born. (Yes I'm bad at naming stuff 😛)

The file extension is still *.md* to avoid configuring my text editors. It currently supports:

@html
<ul>
    <li>Headings: using <code class="inline">#</code>. From level 1 to 6.</li>
    <li><strong>Bold</strong>: <code class="inline">**Bold**</code>.</li>
    <li><em>Italic</em>: <code class="inline">*italic*</code>.</li>
    <li><s>Strikethrough</s>: <code class="inline">~~strikethrough~~</code>.</li>
    <li>Links:
        <ul>
            <li><code class="inline">[I'm an inline-style link](https://www.google.com)</code></li>
            <li><code class="inline">[I'm an inline-style link with title](https://www.google.com "Google's Homepage")</code></li>
        </ul>
    </li>
    <li>Images: <code class="inline">![alt text](https://placekitten.com/200/300)</code></li>
    <li>Horizontal rule: <code class="inline">---</code>.</li>
    <li>Code block: only languages I tend to use are supported. You are going to find them in the following <a href="https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/src/MarkupLanguage/Markdawn/Node/Kind/Html/Code/Language.php">enum</a>.</li>
    <li>Blockquotes</li>
    <li>HTML: for flexibility. Starting with <code class="inline">@html</code> and ending with <code class="inline">@endhtml</code>.</li>
    <li>Metadata: to define variables. Starting with <code class="inline">@metadata</code> and ending with <code class="inline">@endmetadata</code>.</li>
</ul>
@endhtml

#### How to produce HTML from a Markdawn file

I had some fun in the past by writing my own programming language
(for instance with [https://monkeylang.org/](https://monkeylang.org/)
but in [Rust](https://www.rust-lang.org/fr) instead of [Go](https://go.dev/) and then in [D](https://dlang.org/) etc..)
so I did not really hesitate about the approach.

> Using a full regex approach was not chosen because I don't like to maintain them (remember the purpose was to have fun 😛).

The Markdawn file is the input of a lexer which produces tokens.
The parser generates an abstract syntax tree from the tokens.
The compiler creates a "compiled document" object which contains the metadata and the generated HTML.

```php
public function fileToDocument(string $filepath): Compiled\Document
{
    return $this->compiler->compile(
        $this->parser->parse(
            $this->lexer->tokenize(Source::fromFilePath($filepath))
        ),
    );
}
```

Writing the test for the compiler allow me to define the nodes of the abstract syntax tree and develop the compiler itself.
The test is pretty basic: from a defined abstract syntax tree I expect a specific HTML.

We have two kinds of nodes: HTML nodes and Metadata nodes. They serve a different purpose.
HTML nodes have to be compiled to HTML whereas Metadata nodes contain the variables needed to choose the layout,
define the title and whatever I am going to need.
The Metadata part was developed at the end when all the work to convert a Markdawn file to HTML was already finish.

Same for the parser: first writing the test to see what was needed and letting the architecture emerged from it and then develop it really.
And again the same for the lexer - even though I quickly created a test that combined the lexer, parser and compiler
because it was quicker than testing only the lexer (and enough at this point).
It is the test for what I called later the "interpreter" (I did not have a better name in mind, and I still don't 🤔).

If you look at the [code](https://gitlab.com/beram-training/piggy-static/-/tree/01dc5e88172694fdca72f07b79743dea092233bd/src/MarkupLanguage/Markdawn)
you could see that there is more feature in the compiler than in the parser, and more in the parser than in the lexer.
For instance, the support of unordered list was planned and implemented in the compiler and dropped in the parser.
That's because time was passing by, so I had to prioritise the tasks.

To be honest, those parts were the most time-consuming and also the funniest to implement and think about.
I may write a more detailed post to explain them correctly.
I'm not very happy with this Markdawn part even though it does the work well for the needs
and the challenge! (It will allow me to play with legacy code in a fun way one day 😛🤓)

#### Focus on the test

I did not write all the tests first and then developed the related part.
It was done baby step by baby step.

For instance, if we look at the [ParserTest](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/tests/MarkupLanguage/Markdawn/ParserTest.php),
it was nearly done each test case at a time. The first test case looked like:

```php
yield [
    [new Heading1([new Text('Title h1')])],
    [
        new Token(TokenType::HEADING1, '#'),
        new Token(TokenType::SPACE, ' '),
        new Token(TokenType::TEXT, 'Title h1'),
        new Token(TokenType::EOL, '\n'),
        new Token(TokenType::EOF, ''),
    ],
];
```

I stopped here and begun the parser development to support only this case.
I continued step by step or test case by test case if you prefer.
Each time, I knew whether something had been broken or not.

Now it looks like this:

```php
yield [
    new Document([new Heading1([new Text('Title h1')])], []),
    [
        new Token(TokenType::HEADING1, '#'),
        new Token(TokenType::SPACE, ' '),
        new Token(TokenType::TEXT, 'Title h1'),
        new Token(TokenType::EOL, '\n'),
        new Token(TokenType::EOF, ''),
    ],
];
```

The idea about the "Document" node came at the end when the metadata part was implemented.
I had to update all the test cases with this new information.

Those who already worked with me knows that I change my mind quiet a lot when I develop things.
All those tests allow me to do it and refactor constantly without the fear of breaking something.

I don't really care about what kind of tests I write nor the language I use to write them as long as they help me bring out the code
step by step.
Tests should be as fast, repeatable and understandable as possible.

If you look at the tests for this project you could notice some major errors.
For instance, only happy paths are tested. Happy paths were enough to develop within the context.
They may not be enough for the future 😉.

### Dependency Injection Container

Even though I'm pretty sure it is going to be overkill, I'd like to avoid having to do

```php
<?php

new Interpreter(
    new Lexer,
    new Parser,
    new Compiler,
);
```

everytime I need the interpreter. Same goes for other parts that will come.

So let's implements a minimalist **Dependency Injection Container**!

I cannot use the [PSR-11 Container Interface](https://github.com/php-fig/container) dependency but it is not a big deal
since I'm already familiar with it.
I'm not going to reinvent the wheel here! I'll try to be as close as possible to it in order to gain some time.

> Should I consider this cheating? 🤷
>
> I don't need to take a look at it.
> It is only about respecting contracts.

@html
<p>
The <code class="inline">Container</code> contain <code class="inline">Definition</code>s of objects.
A <code class="inline">Definition</code> is the part that know how to instantiate the object it defines.
</p>
@endhtml

Our container is immutable and is able to detect circular dependency.

If you are curious and like to know how to use it look at
the [ContainerTest](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/tests/DependencyInjection/ContainerTest.php)
(tests are also documentations 😛)
or the [container.php](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/config/container.php) file.

```php
<?php

declare(strict_types=1);

use beram\PiggyStatic\DependencyInjection\Container;
use beram\PiggyStatic\DependencyInjection\Definition as Def;
[...]

return new Container([
    Lexer::class => new Def(fn() => new Lexer()),
    Parser::class => new Def(fn() => new Parser()),
    Compiler::class => new Def(fn() => new Compiler()),
    Interpreter::class => new Def(fn(Container $container) => new Interpreter(
        $container->get(Lexer::class),
        $container->get(Parser::class),
        $container->get(Compiler::class)
    )),
    Generate::class => new Def(fn(Container $container) => new Generate($container->get(Interpreter::class))),
]);
```

I'm not going to explain more for the moment and keep it for another blog post.

### Command Line

I'm developing a command line tool so I need to be able to parse the input of the command.

Let's take a shortcut and use the PHP function [getopt](https://www.php.net/manual/en/function.getopt.php)! 🤓

@html
<blockquote>
<p>
Shortcut because I'm familiar with this function,
I love writing bash script (see <a href="https://www.gnu.org/software/bash/manual/html_node/Bourne-Shell-Builtins.html">getopts</a>),
I'm familiar with <a href="https://www.gnu.org/software/libc/manual/html_node/Getopt.html">getopt function from the GNU C Library</a>
etc.. etc.. 😛
</p>
</blockquote>
@endhtml

If you take a look at [the code](https://gitlab.com/beram-training/piggy-static/-/tree/01dc5e88172694fdca72f07b79743dea092233bd/src/CommandLine),
you'll see I added some abstraction around.

Also you could see with the [test](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/tests/CommandLine/CommandTest.php) that
an "Hello world" command looks like:

```php
$command = new class() extends Command {
    protected function configure(): Config
    {
        return Config::default('hello')
            ->withOption('name', AllowedOption\Value::required)
            ;
    }

    protected function execute(
        Input $input,
        Output $output,
    ): ExitCode {
        $name = $input->options->has('name') ? $input->options->get('name') : 'world';
        $output->out(\sprintf('Hello %s', $name));

        return ExitCode::OK;
    }
};
```

@html
<p>
The <code class="inline">Input</code> class is based on <a href="https://www.php.net/manual/en/function.getopt.php">getopt</a>.
So no arguments (for the moments), only options.
</p>
@endhtml

To output text to the user we have an [Output](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/src/CommandLine/Output.php) interface:

```php
interface Output
{
    public function out(string $text): void;

    public function error(string $text): void;
}
```

And currently two implementations.
[One](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/src/CommandLine/Output/StdStream.php) writing on [standard streams](https://www.gnu.org/software/libc/manual/html_node/Standard-Streams.html)
*stdout* and *stderr*:

```php
<?php

declare(strict_types=1);

namespace beram\PiggyStatic\CommandLine\Output;

use beram\PiggyStatic\CommandLine\Output;

final class StdStream implements Output
{
    public function out(string $text): void
    {
        $output = \fopen('php://stdout', 'w');
        \fwrite($output, $text.\PHP_EOL);
        \fclose($output);
    }

    public function error(string $text): void
    {
        $output = \fopen('php://stderr', 'w');
        \fwrite($output, $text.\PHP_EOL);
        \fclose($output);
    }
}
```

@html
<blockquote>
<p>
We purposely don't use constants like <a href="https://www.php.net/manual/en/features.commandline.io-streams.php">STDOUT or STDERR</a> for the moment to checking their availability.
</p>
</blockquote>
@endhtml

The [other one](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/src/CommandLine/Test/TestOutput.php) stocks the data for test purposes.

### The generator

Finally! We can start the final step! The static site generator!

The core of this part will be the command to create.

Here again, just take a look at the [tests](https://gitlab.com/beram-training/piggy-static/-/tree/01dc5e88172694fdca72f07b79743dea092233bd/tests/WebsiteGenerator)
to see how it works.

```php
<?php

declare(strict_types=1);

namespace Tests\beram\PiggyStatic\WebsiteGenerator\Command;

use beram\PiggyStatic\CommandLine\ExitCode;
use beram\PiggyStatic\CommandLine\Input;
use beram\PiggyStatic\CommandLine\Test\CommandTester;
use beram\PiggyStatic\CommandLine\Test\TestOutput;
use beram\PiggyStatic\FileSystem\FileSystem;
use beram\PiggyStatic\Kernel;
use beram\PiggyStatic\WebsiteGenerator\Command\Generate;
use PHPUnit\Framework\TestCase;

final class GenerateTest extends TestCase
{
    private const FIXTURES_BUILD_DIRECTORY = __DIR__.'/../fixtures/blog/build';
    private const EXPECTED_BUILD_DIRECTORY = __DIR__.'/../fixtures/blog_expected';

    public function test(): void
    {
        $kernel = new Kernel();
        $command = $kernel->container->get(Generate::class);
        $commandTester = new CommandTester();
        $output = new TestOutput();

        $exitCode = $commandTester->execute($command, new Input(new Input\Options(['config' => __DIR__.'/../fixtures/blog/.piggy-static.php'])), $output);

        self::assertSame(ExitCode::OK, $exitCode);
        self::assertSame([], $output->getOut());
        self::assertSame([], $output->getError());

        self::assertDirectoryExists(self::FIXTURES_BUILD_DIRECTORY);
        self::assertDirectoryExists(self::FIXTURES_BUILD_DIRECTORY.'/assets');
        self::assertDirectoryDoesNotExist(self::FIXTURES_BUILD_DIRECTORY.'/_layouts');
        foreach (['/index.html', '/articles/first.html', '/assets/css/main.css'] as $file) {
            self::assertFileEquals(self::EXPECTED_BUILD_DIRECTORY.$file, self::FIXTURES_BUILD_DIRECTORY.$file);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        FileSystem::delete(self::FIXTURES_BUILD_DIRECTORY);
    }
}
```

The only way to configure the generator is from a configuration file written in PHP:

```php
<?php

use beram\PiggyStatic\WebsiteGenerator\Config;
use beram\PiggyStatic\WebsiteGenerator\Layout;

return Config::default()
    ->withSrc(__DIR__)
    ->withDest(__DIR__.'/build')
    ->withFilesToExclude(['_layouts', 'build'])
    ->withLayout('default', new Layout(__DIR__.'/_layouts/default.html'))
    ->withLayout('article', new Layout(__DIR__.'/_layouts/article.html'))
    ;
```

It will allow us to have everything typed with a simple user experience if you know PHP
(I'm the targeted user so I hope I know PHP enough to do this 😅).

For the moment, it is not possible to configure the **assets** folder.
It has to be named **assets** and has to be located at the root of the source directory.

The layouts (i.e. templates) are simple PHP files.
Just take a look at the [Layout class](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/src/WebsiteGenerator/Layout.php)
to understand how they are rendered - just plain old combination of
[ob_start](https://www.php.net/manual/en/function.ob-start.php),
[include](https://www.php.net/manual/en/function.include.php)
and [ob_get_clean](https://www.php.net/manual/en/function.ob-get-clean.php) so nothing complex 😛.

This part makes me create a [FileSystem](https://gitlab.com/beram-training/piggy-static/-/tree/01dc5e88172694fdca72f07b79743dea092233bd/src/FileSystem)
component. It is minimalist but since working with the file system in PHP requires some boilerplate
it really helps to have a code easier to read and understand.

### Compile everything into a Phar

I didn't plan to implement this part, but I still had time and it was something I had in mind.
I wanted one day to be able to work on the generator and the blog in different Git repositories (without Git submodules).

Having a [Phar](https://www.php.net/manual/en/intro.phar.php) will allow it so this day is now! 🎉

The [compiler](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/tools/compile.php) is really simple.
It just uses the phar extension provided by PHP.

```php
<?php

declare(strict_types=1);

/**
 * @file
 * Compile to phar file.
 *
 * Must be run from the root.
 *
 * Usage: php --define phar.readonly=0 tools/compile.php
 */

return (new class() {
    public function __invoke(string $file): int
    {
        $this->clean($file);
        @\mkdir(\dirname($file), 0755, true);

        $phar = new \Phar($file);
        $phar->buildFromDirectory(__DIR__.'/../', $this->excludeFromPharPattern());
        $phar->setStub($phar->createDefaultStub('bin/generate.php'));
        $phar->compressFiles(\Phar::GZ);

        return 0;
    }

    private function clean(string $file): void
    {
        if (\file_exists($file)) {
            \unlink($file);
        }

        $gz = \sprintf('%.gz', $file);
        if (\file_exists($gz)) {
            \unlink($gz);
        }
    }

    private function excludeFromPharPattern(): string
    {
        $fromRoot = fn (string $file): string => \preg_quote(__DIR__.'/../'.$file, '#');

        return \sprintf('#(%s)#', \implode(
            '|',
            [
                '^'.$fromRoot('src'),
                '^'.$fromRoot('config'),
                '^'.$fromRoot('bin'),
                '^'.$fromRoot('autoload.php').'$',
            ],
        ));
    }
})('build/piggy-static-generate.phar');
```

The [CI](https://gitlab.com/beram-training/piggy-static/-/blob/01dc5e88172694fdca72f07b79743dea092233bd/.gitlab-ci.yml) will build the phar for us using
this script like:

```shell
$ php --define phar.readonly=0 tools/compile.php
```

## Conclusion

Was it fun? Hell yeah!

I'm happily surprised to have this infection result at the end (and I'm eager to improve it 🤓):

```php
$ php tools/infection

[...]

488 mutations were generated:
     398 mutants were killed
       0 mutants were configured to be ignored
      73 mutants were not covered by tests
       3 covered mutants were not detected
       9 errors were encountered
       0 syntax errors were encountered
       5 time outs were encountered
       0 mutants required more time than configured

Metrics:
         Mutation Score Indicator (MSI): 84%
         Mutation Code Coverage: 85%
         Covered Code MSI: 99%

Please note that some mutants will inevitably be harmless (i.e. false positives).

Time: 4m 8s. Memory: 0.04GB
```

I'm 100% sure that when I will look at the code base another time I will be screaming what an idiot I am to have
done some parts the way they are! (I already did in fact 😅)

And that's okay!

It will mean that I have evolved (for the better or for the worst I don't know the future 😛) and that I will have to play
another game: playing with legacy code that I'm solely responsible for!

@html
<blockquote>
<p>PS: The repository is <a href="https://gitlab.com/beram-training/piggy-static">here</a>.</p>
</blockquote>
@endhtml
