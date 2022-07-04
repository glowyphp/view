<?php

declare(strict_types=1);

namespace Glowy\View;

use Glowy\View\View;

if (! function_exists('view')) {
    /**
     * Create a new view instance.
     *
     * @param string|null $view Name of the view file.
     * @param array  $data Array of view variables.
     * 
     * @return \Glowy\View\View
     */
    function view(string|null $view = null, array $data = []): \Glowy\View\View
    {
        return new View($view, $data);
    }
}

if (! function_exists('e')) {
    /**
     * Escape string.
     *
     * @param string $string       Name of the view file
     * @param int    $flags        A bitmask of one or more of the following flags, which specify how to handle quotes, invalid code unit sequences and the used document type.
     *                             The default is ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401.
     * @param string $encoding     An optional argument defining the encoding used when converting characters.
     * @param bool   $doubleEncode When doubleEncode is turned off PHP will not encode existing html entities, the default is to convert everything.
     * 
     * @return string
     */
    function e(string $string, int $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, $encoding = 'UTF-8', $doubleEncode = true): string
    {
        return htmlspecialchars($string, $flags, $encoding, $doubleEncode);
    }
}
