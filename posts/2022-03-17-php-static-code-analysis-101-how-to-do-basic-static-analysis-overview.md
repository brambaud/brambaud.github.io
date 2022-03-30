@metadata
layout: post_series
title: [PHP Static Code Analysis 101] How to do Static Code Analysis? An overview
title_display: How to do Static Code Analysis? An overview
series: PHP Static Code Analysis 101
date: 2022-03-17
canonical: 2022-03-17-php-static-code-analysis-101-how-to-do-basic-static-analysis-overview.html
excerpt: What if I told you PHP already uses the concept behind static code analysis?
@endmetadata

<div class="info">
<p>
This article comes from the series
"PHP Static Code Analysis 101 - An introduction to what's behind PHP static code analysis"
</p>
<ul>
<li><a href="/posts/2022-03-15-php-static-code-analysis-101-what-is-code-analysis.html">What is code analysis?</a></li>
<li><a href="/posts/2022-03-17-php-static-code-analysis-101-how-to-do-basic-static-analysis-overview.html">How to do Static Code Analysis? An overview</a></li>
</ul>
<p>This series is not finished yet!</p>
</div>

If you remember from the previous post <a href="/posts/2022-03-15-php-static-code-analysis-101-what-is-code-analysis.html">What is code analysis?</a>,
you know that with static analysis we don't run/execute the code.
Since we don't do that we have to find methods to analyse it 😛

Maybe your first thought is to use regular expressions.
Indeed it works for some parts,
for instance if you check <a href="https://github.com/FriendsOfPHP/PHP-CS-Fixer/tree/v0.1.0">the first versions of PHP-CS-Fixer</a>
they mainly used them. Nevertheless, the codebase risks becoming rather complex, or you're going to be stuck at some point.
I'm not sure what the regular expression to know if a variable's type has changed could look like 🤷

Other technics are mainly used because they are simpler to maintain and allow analysing more things.

To know which ones we could directly look at how PHP works!

PHP compiles one file at a time at runtime. If we take this file:

```php
<?php

// Chookity
echo "I love ponies";
```

PHP will compile it into the following opcodes:

```php
000 ECHO string("I love ponies")
001 RETURN int(1)
```

At the end, those opcodes are "executed" on the Zend Virtual Machine.
In fact an opcode is a VM instruction.

> If you would like to know more about the Zend Virtual Machine you should definitively read the article
> <a href="https://www.npopov.com/2017/04/14/PHP-7-Virtual-machine.html">PHP 7 Virtual Machine</a>
> from <a href="https://www.npopov.com/aboutMe.html">Nikita Popov</a>
> and <a href="https://www.phpinternalsbook.com/php7/zend_engine.html">the PHP Internals Book section about it</a>.

To produce opcodes from our code, PHP will use several technics from compilation theory.

<picture>
    <source media="all and (max-width: 639px)" srcset="/assets/images/post/static-analysis/how-php-works-mobile.svg">
    <img style="width: 100%;" alt="overview how php works" src="/assets/images/post/static-analysis/how-php-works.svg">
</picture>

A lexer converts the code into tokens, that's lexical analysis.
A parser converts the tokens into an Abstract Syntax Tree (AST), that's syntax analysis.

Let's take the following sentence : "Do you love ponies?".

To understand it, first you decompose it into symbols:

<ul>
    <li>a string: "Do"</li>
    <li>a whitespace: " "</li>
    <li>a string: "you"</li>
    <li>a whitespace: " "</li>
    <li>a string: "love"</li>
    <li>a whitespace: " "</li>
    <li>a string: "ponies"</li>
    <li>a special string: a question mark "?"</li>
</ul>

Then it is easier to understand that "Do" and "love" are words because they are separated by a whitespace.
You know it is a question because you have a question mark at the end. You know the grammar rules.
To understand the phrase you need to know that "Love" is the present tense of the verb "to love",
"you" is the subject of the sentence and either the second-person singular
or the second-person plural depending on the context, the plural noun "ponies" is the object of the sentence.

We need to do kind of the same work with programming language.

A token is a symbol with a specific meaning. With this stream of tokens it is simpler to create an AST
to understand the syntax of the language.

Tl;DR:
We start from a big string.
We decompose it into a "linear" stream of tokens that we use to create a tree (AST).
This tree helps us understand the syntax and what we should instruct the machine to do.

Tokens and AST are the core of static analysis!

In the next articles from this series we'll learn more on how to do static analysis with Tokens.
So stay tune!
