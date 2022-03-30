@metadata
layout: post_series
title: [PHP Static Code Analysis 101] What is Static Code Analysis?
title_display: What is Static Code Analysis?
series: PHP Static Code Analysis 101
date: 2022-03-15
canonical: 2022-03-15-php-static-code-analysis-101-what-is-code-analysis.html
excerpt: A very short introduction to static code analysis. What if I told you that PHP itself already uses the concept behind static code analysis?
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

## What is Code Analysis?

To understand what is **static code analysis** let's try to understand why we want to analyse the code.

With **code analysis** we are trying to determine the behaviors of a program.
We could look for a lot of different things like:
Is it robust? Is it safe? Is it buggy? Does it respect some conventions? etc..
We want to know it better! And we want to do this automatically!

Let's take an example code from [Psalm](https://psalm.dev/articles/detect-security-vulnerabilities-with-psalm):

```php
<?php // --taint-analysis

/** @psalm-immutable */
class User {
    public $id;
    public function __construct($userId) {
        $this->id = $userId;
    }
}

class UserUpdater {
    public static function deleteUser(PDO $pdo, User $user) : void {
        $pdo->exec("delete from users where user_id = " . $user->id);
    }
}

$userObj = new User($_GET["user_id"]);

UserUpdater::deleteUser(new PDO(), $userObj);
```

<p>
It is fairly easy to spot the security issue here.
We use <code class="inline">$_GET["user_id"]</code> directly in a SQL query.
It is an input and we should not trust inputs.
Remember to be <a href="https://en.wikipedia.org/wiki/Defensive_programming">defensive</a> and never trust the user.
(In fact, never trust anyone 😛)
</p>

> It has nothing to do with the subject of this article
> but I advise you to take a look at <a href="https://www.youtube.com/watch?v=Gl9td0zGLhw">Ocramius talk about extremely defensive PHP</a>.

It is fairly easy here because we have all the code right in front our eyes.
Nevertheless, we are humans. We make mistakes.
We cannot have all the code in our mind. Our cognitive load is limited.
We are going to miss some of those errors.

Automate it allow us to focus on what's important. For instance, how to fix this issue.

> It is when we have a working code that the real fun and important work begins 😉

## Difference between Static and Dynamic code analysis

Inevitably if we talk about **static code analysis** it means that **dynamic code analysis** is a thing too 🤓

With static analysis we don't run/execute the program to determine its behaviors.

With dynamic analysis we have to run/execute it to monitore/observe the
execution states.
Computing the code coverage according to a test suite is a standard dynamic analysis technique.

It is generally quicker to statically analyse a program.
We don't need the full app running (no database required etc..) for instance.

But it could be challenging to!
For instance, key information in Drupal are determined at runtime or stored in the database by default like the service container definition.

> They are for good reasons we won't explain right now.
> <a href="https://www.drupal.org/node/2497243">The issue introducing the service container definition stored in the database</a> is an interesting read 😉

In the next articles from this series we are going to overview how to do static analysis in PHP.
So stay tune!

<div class="info">
 <p>Next article: <a href="/posts/2022-03-17-php-static-code-analysis-101-how-to-do-basic-static-analysis-overview.html">How to do basic Static Code Analysis? An overview</a></p>
</div>
