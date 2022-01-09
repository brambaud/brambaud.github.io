<?php

declare(strict_types=1);

function part(string $id): string {
    \ob_start();
    include \sprintf('%s/../_layouts/parts/%s.phtml', __DIR__, $id);
    return \ob_get_clean();
}
