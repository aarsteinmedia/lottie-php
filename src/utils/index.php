<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Helpers\Constants;

use function LottiePHP\Utils\Pooling\domDocumentPool;

function addStyles(\DOMElement $el, array $styles)
{
    $existing = parseStyle($el->getAttribute('style'));

    $newStyle = buildStyle(array_merge($existing, $styles));
    $el->setAttribute('style', $newStyle);
}

function parseStyle(?string $style): array
{
    $style = trim((string) $style);
    if ('' === $style) {
        return [];
    }
    $out = [];
    $len = strlen($style);
    $i = 0;
    $buf = '';
    $inS = false; // single quotes
    $inD = false; // double quotes
    $paren = 0;   // parentheses nesting, e.g. url(...)
    $flushDecl = function (string $decl) use (&$out): void {
        $decl = trim($decl);
        if ('' === $decl) {
            return;
        }
        // Split on the first ":" only
        $pos = strpos($decl, ':');
        if (false === $pos) {
            return;
        }
        $prop = trim(substr($decl, 0, $pos));
        $val = trim(substr($decl, $pos + 1));
        if ('' === $prop) {
            return;
        }
        $out[strtolower($prop)] = $val;
    };
    while ($i < $len) {
        $ch = $style[$i];
        if (!$inD && "'" === $ch) {
            $inS = !$inS;
            $buf .= $ch;
            ++$i;

            continue;
        }
        if (!$inS && '"' === $ch) {
            $inD = !$inD;
            $buf .= $ch;
            ++$i;

            continue;
        }
        if (!$inS && !$inD) {
            if ('(' === $ch) {
                ++$paren;
                $buf .= $ch;
                ++$i;

                continue;
            }
            if (')' === $ch && $paren > 0) {
                --$paren;
                $buf .= $ch;
                ++$i;

                continue;
            }
            // End of declaration (ignore semicolons inside quotes/parentheses)
            if (';' === $ch && 0 === $paren) {
                $flushDecl($buf);
                $buf = '';
                ++$i;

                continue;
            }
        }
        $buf .= $ch;
        ++$i;
    }
    $flushDecl($buf);

    return $out;
}

function buildStyle(array $styles): string
{
    $parts = [];
    foreach ($styles as $property => $value) {
        $parts[] = "{$property}:{$value}";
    }

    return implode('; ', $parts);
}

/**
 * Read one UTF-8 character starting at a byte offset (for Lottie text parsing).
 */
function utf8CharAtByte(string $str, int $byteIndex): string
{
    if ($byteIndex < 0 || $byteIndex >= strlen($str)) {
        return '';
    }

    $byte = ord($str[$byteIndex]);

    if ($byte < 0x80) {
        return $str[$byteIndex];
    }
    if (($byte & 0xE0) === 0xC0 && $byteIndex + 1 < strlen($str)) {
        return substr($str, $byteIndex, 2);
    }
    if (($byte & 0xF0) === 0xE0 && $byteIndex + 2 < strlen($str)) {
        return substr($str, $byteIndex, 3);
    }
    if (($byte & 0xF8) === 0xF0 && $byteIndex + 3 < strlen($str)) {
        return substr($str, $byteIndex, 4);
    }

    return $str[$byteIndex];
}

function charCodeAt(string $str, int $index): int
{
    $char = utf8CharAtByte($str, $index);

    if ('' === $char) {
        return 0;
    }

    if (function_exists('mb_ord')) {
        return mb_ord($char, 'UTF-8') ?? 0;
    }

    $first = ord($char[0]);

    if ($first < 0x80) {
        return $first;
    }

    return ord($char[0]);
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

function createNS(string $type)
{
    return domDocumentPool()->createElementNS(Constants::$namespaceSVG, $type);
}

function createTag(string $type)
{
    return domDocumentPool()->createElement($type);
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

function getBlendMode(int $mode = 16)
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
        16 => 'normal',
        2 => 'screen',
        3 => 'overlay',
        4 => 'darken',
        5 => 'lighten',
        6 => 'color-dodge',
        7 => 'color-burn',
        8 => 'hard-light',
        9 => 'soft-light',
    ];

    return $blendModeEnums[$mode] ?? 'normal';
}

function isLottie(array $json)
{
    $mandatory = [
        'v',
        'ip',
        'op',
        'layers',
        'fr',
        'w',
        'h',
    ];

    return array_all($mandatory, fn (string $field) => array_key_exists($field, $json));
}

function rgbToHex(
    float $rVal,
    float $gVal,
    float $bVal
) {
    $colorMap = [];
    $hex = null;

    for ($i = 0; $i < 256; ++$i) {
        $hex = dechex($i);
        $colorMap[$i] = 1 === strlen($hex) ? "0{$hex}" : $hex;
    }

    $r = $rVal;
    $g = $gVal;
    $b = $bVal;

    if ($rVal < 0) {
        $r = 0;
    }
    if ($gVal < 0) {
        $g = 0;
    }
    if ($bVal < 0) {
        $b = 0;
    }

    return "#{$colorMap[$r]}{$colorMap[$g]}{$colorMap[$b]}";
}

function toRGBString(?array $v)
{
    if (!$v) {
        return 'rgb(0,0,0)';
    }

    // v is already scaled to 0..255 by property factory; keep it fast and allocation-light.
    $r = (int) ($v[0] ?? 0);
    $g = (int) ($v[1] ?? 0);
    $b = (int) ($v[2] ?? 0);

    // Clamp just in case upstream sends values outside the expected range.
    $r = max(0, min(255, $r));
    $g = max(0, min(255, $g));
    $b = max(0, min(255, $b));

    return "rgb({$r},{$g},{$b})";
}
