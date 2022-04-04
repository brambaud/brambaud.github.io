<?php

declare(strict_types=1);

use beram\PiggyStatic\WebsiteGenerator\Source;
use beram\PiggyStatic\WebsiteGenerator\Source\Collection as Sources;

function part(string $id): string {
    \ob_start();
    include \sprintf('%s/../_layouts/parts/%s.phtml', __DIR__, $id);
    return \ob_get_clean();
}

function posts_from_sources(Sources $sources): array {
    $posts = new CallbackFilterIterator($sources->getIterator(), static function (Source $source): bool {
        return \str_starts_with($source->relativePathToSrc, '/posts/');
    });

    $posts = \iterator_to_array($posts);
    \krsort($posts);

    return $posts;
}

function unique_key(): string {
    return \bin2hex(\random_bytes(8));
}
