<?php

declare(strict_types=1);

use Atomastic\View\View;

if (! function_exists('view')) {
    function view(string $view, array $data = []): View
    {
        return new View($view, $data);
    }
}
