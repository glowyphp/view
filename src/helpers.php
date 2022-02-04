<?php

declare(strict_types=1);

use Glowy\View\View;

if (! function_exists('view')) {
    /**
     * Create a new view instance.
     *
     * @param string $view Name of the view file.
     * @param array  $data Array of view variables.
     * 
     * @return View
     */
    function view(string $view, array $data = []): View
    {
        return new View($view, $data);
    }
}

if (! function_exists('e')) {
    /**
     * Escape string.
     *
     * @param string $string Name of the view file
     * @param int    $flags  A bitmask of one or more of the following flags, which specify how to handle quotes, invalid code unit sequences and the used document type.
     *                       The default is ENT_COMPAT | ENT_HTML401.
     * 
     * @return string
     */
    function e(string $string, int $flags = ENT_COMPAT | ENT_HTML401): string
    {
        return htmlspecialchars($string, $flags, 'UTF-8');
    }
}
