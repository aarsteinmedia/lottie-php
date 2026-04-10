<?php

namespace LottiePHP\Utils;

use DOMElement;

function addStyles(DOMElement $el, array $styles)
{

    $existing = $el->getAttribute('style');
    $new = buildStyle($styles);
    $el->setAttribute('style', $existing ? $existing . '; ' . $new : $new);

}


function buildStyle(array $styles): string
{
    $parts = [];
    foreach ($styles as $property => $value) {
        $parts[] = "$property:$value";
    }
    return implode('; ', $parts);
}
