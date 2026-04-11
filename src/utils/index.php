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

function addClasses(DOMElement $el, array $classes) {
    $existing = $el->getAttribute('class');
    $new = implode(' ', $classes);
    $el->setAttribute('class', $existing ? $existing . ' ' . $new : $new);
}

function createElementID(): string {
    static $count = 0;
    $count++;
    return '__lottie_element_' . $count;
}

function createSizedArray(int $length) {
  return array_fill(0, $length, null);
}