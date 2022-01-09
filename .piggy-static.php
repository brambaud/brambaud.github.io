<?php

use beram\PiggyStatic\WebsiteGenerator\Config;
use beram\PiggyStatic\WebsiteGenerator\Layout;

include_once __DIR__.'/.piggy-static/helpers.php';

return Config::default()
    ->withSrc(__DIR__)
    ->withFilesToExclude(['tools', '_layouts', '.piggy-static', 'assets', 'build'])
    ->withDest(__DIR__.'/build')
    ->withLayout('homepage', new Layout(__DIR__.'/_layouts/homepage.phtml'))
    ->withLayout('post', new Layout(__DIR__.'/_layouts/post.phtml'))
    ->withLayout('basic', new Layout(__DIR__.'/_layouts/basic.phtml'))
    ;
