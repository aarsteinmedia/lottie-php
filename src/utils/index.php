<?php

namespace LottiePHP\Utils;

// declare(strict_types=1);

use DOMDocument;
use DOMElement;
use LottiePHP\Utils\Enums\ArrayType;

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

function createTypedArray(ArrayType $type, int $length, bool $useTypedInit = true): array
{
    if (!$useTypedInit) {
        return createRegularArray($type, $length);
    }
    return match ($type) {
        ArrayType::Float32 => array_fill(0, $length, 0.0),
        ArrayType::Int16 => array_fill(0, $length, 0),
        ArrayType::Uint8c => array_fill(0, $length, 0),
        default => createRegularArray($type, $length),
    };
}
function createRegularArray(ArrayType $type, int $length): array
{
    $value = match ($type) {
        ArrayType::Int16, ArrayType::Uint8c => 1,
        default => 1.1,
    };
    return array_fill(0, $length, $value);
}
/**
 * @template T
 * @param int $length
 * @return list<T|null>
 */
function createSizedArray(int $length): array
{
    return array_fill(0, $length, null);
}

function createNS(string $type, ?DOMDocument $domFromProps) {
    $dom = $domFromProps ?? new DOMDocument('1.0', 'UTF-8');

    $dom->createElementNS('http://www.w3.org/2000/svg', 'svg');

    return $dom->createElementNS('http://www.w3.org/2000/svg', $type);
}

function getBlendMode($mode = 16) {
    $blendModeEnums = [
        0 => 'source-over',
        1 => 'multiply',
        10 => 'difference',
        11 => 'exclusion',
        12 => 'hue',
        13 => 'saturation',
        14 => 'color',
        15 => 'luminosity',
        2 => 'screen',
        3 => 'overlay',
        4 => 'darken',
        5 => 'lighten',
        6 => 'color-dodge',
        7 => 'color-burn',
        8 => 'hard-light',
        9 => 'soft-light',
    ];

  return $blendModeEnums[$mode] || '';
}