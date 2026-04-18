<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Helpers\Constants;

function addStyles(\DOMElement $el, array $styles)
{
    $existing = $el->getAttribute('style');
    $new = buildStyle($styles);
    $el->setAttribute('style', $existing ? $existing.'; '.$new : $new);
}

function buildStyle(array $styles): string
{
    $parts = [];
    foreach ($styles as $property => $value) {
        $parts[] = "{$property}:{$value}";
    }

    return implode('; ', $parts);
}

function charCodeAt(string $str, int $index): int
{
    return ord(substr($str, $index, 1));
}

function addClasses(\DOMElement $el, array $classes)
{
    $existing = $el->getAttribute('class');
    $new = implode(' ', $classes);
    $el->setAttribute('class', $existing ? $existing.' '.$new : $new);
}

function createElementID(): string
{
    static $count = 0;
    ++$count;

    return '__lottie_element_'.$count;
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
 *
 * @return list<null|T>
 */
function createSizedArray(int $length): array
{
    return array_fill(0, $length, null);
}

function createNS(string $type, ?\DOMDocument $domFromProps = null)
{
    $dom = $domFromProps ?? new \DOMDocument('1.0', 'UTF-8');

    return $dom->createElementNS(Constants::$namespaceSVG, $type);
}

function floatEqual(int $a, int $b)
{
    return abs($a - $b) * 100000 <= min(abs($a), abs($b));
}

function floatZero(int $f)
{
    return abs($f) <= 0.00001;
}

function pointEqual(array $p1, array $p2)
{
    return floatEqual($p1[0], $p2[0]) && floatEqual($p1[1], $p2[1]);
}

function getBlendMode($mode = 16)
{
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
