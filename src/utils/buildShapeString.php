<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Shapes\ShapePath;

/**
 * @param null|array{0: float|int, 1: float|int} $a
 * @param null|array{0: float|int, 1: float|int} $b
 */
function pathPointsEqual(?array $a, ?array $b, float $epsilon = 0.01): bool
{
    return abs(($a[0] ?? 0) - ($b[0] ?? 0)) < $epsilon
        && abs(($a[1] ?? 0) - ($b[1] ?? 0)) < $epsilon;
}

/**
 * Whether a tangent value is an absolute control point rather than a relative offset.
 *
 * @param null|array{0: float|int, 1: float|int} $tangent
 * @param null|array{0: float|int, 1: float|int} $vertex
 */
function tangentControlPointLooksAbsolute(?array $tangent, ?array $vertex, float $epsilon = 0.01): bool
{
    $tx = $tangent[0] ?? 0;
    $ty = $tangent[1] ?? 0;
    $vx = $vertex[0] ?? 0;
    $vy = $vertex[1] ?? 0;

    if (abs($tx) < $epsilon && abs($ty) < $epsilon) {
        return false;
    }

    if (pathPointsEqual($tangent, $vertex, $epsilon)) {
        return true;
    }

    $magnitude = hypot($tx, $ty);
    $relativeMagnitude = hypot($tx - $vx, $ty - $vy);

    // Absolute exports store the control point position; subtracting the vertex yields
    // the much shorter relative tangent vector Lottie expects.
    return $magnitude > $relativeMagnitude * 1.05 + $epsilon;
}

/**
 * Detect glyph/path exports that store absolute bezier control points in i/o
 * instead of Lottie's relative tangent vectors.
 *
 * @param array{v?: array, o?: array, i?: array} $pathData
 */
function pathUsesAbsoluteTangents(array $pathData): bool
{
    if (!isset($pathData['v'], $pathData['o'], $pathData['i'])) {
        return false;
    }

    $vertices = $pathData['v'];
    $length = count($vertices);
    if ($length < 2) {
        return false;
    }

    $absoluteTangents = 0;
    $nearZeroTangents = 0;
    $epsilon = 0.01;

    for ($j = 0; $j < $length; ++$j) {
        $out = $pathData['o'][$j] ?? [0, 0];
        $in = $pathData['i'][$j] ?? [0, 0];
        $vertex = $vertices[$j] ?? [0, 0];

        if (
            abs($out[0]) < $epsilon
            && abs($out[1]) < $epsilon
            && abs($in[0]) < $epsilon
            && abs($in[1]) < $epsilon
        ) {
            ++$nearZeroTangents;
        }

        if (tangentControlPointLooksAbsolute($out, $vertex)) {
            ++$absoluteTangents;
        }
        if (tangentControlPointLooksAbsolute($in, $vertex)) {
            ++$absoluteTangents;
        }
    }

    // Standard Lottie paths use relative tangents; straight segments use [0, 0].
    if ($nearZeroTangents >= (int) ceil($length * 0.5)) {
        return false;
    }

    return $absoluteTangents >= max(2, (int) ceil($length * 0.25));
}

/**
 * Convert absolute i/o control points to relative Lottie tangents.
 *
 * @param array{v?: array, o?: array, i?: array} $pathData
 *
 * @return array{v?: array, o?: array, i?: array}
 */
function normalizePathDataTangents(array $pathData): array
{
    if (!pathUsesAbsoluteTangents($pathData)) {
        return $pathData;
    }

    $length = count($pathData['v'] ?? []);
    for ($j = 0; $j < $length; ++$j) {
        $vertex = $pathData['v'][$j] ?? [0, 0];
        $out = $pathData['o'][$j] ?? [0, 0];
        $in = $pathData['i'][$j] ?? [0, 0];

        $pathData['o'][$j] = [
            ($out[0] ?? 0) - ($vertex[0] ?? 0),
            ($out[1] ?? 0) - ($vertex[1] ?? 0),
        ];
        $pathData['i'][$j] = [
            ($in[0] ?? 0) - ($vertex[0] ?? 0),
            ($in[1] ?? 0) - ($vertex[1] ?? 0),
        ];
    }

    return $pathData;
}

function buildShapeString(
    ShapePath $pathNodes,
    int $length,
    bool $closed,
    Matrix $mat
) {
    if (0 === $length) {
        return '';
    }
    $_o = $pathNodes->o;
    $_i = $pathNodes->i;
    $_v = $pathNodes->v;
    $i = 0;
    $vVector = [$_v[0][0] ?? 0, $_v[0][1] ?? 0];
    $vString = $mat->applyToPointStringified(...$vVector);
    $iVector = [0, 0];
    $iString = '';
    $oVector = [0, 0];
    $oString = '';
    $shapeString = " M{$vString}";

    for ($i = 1; $i < $length; ++$i) {
        // Lottie in/out tangents are relative to the vertex.
        // Out tangent belongs to previous vertex, in tangent belongs to current vertex.
        $prevV = [$_v[$i - 1][0] ?? 0, $_v[$i - 1][1] ?? 0];
        $curV = [$_v[$i][0] ?? 0, $_v[$i][1] ?? 0];

        $oVector = [
            $prevV[0] + ($_o[$i - 1][0] ?? 0),
            $prevV[1] + ($_o[$i - 1][1] ?? 0),
        ];
        $oString = $mat->applyToPointStringified(...$oVector);
        $iVector = [
            $curV[0] + ($_i[$i][0] ?? 0),
            $curV[1] + ($_i[$i][1] ?? 0),
        ];
        $iString = $mat->applyToPointStringified(...$iVector);
        $vVector = $curV;
        $vString = $mat->applyToPointStringified(...$vVector);

        $shapeString .= " C{$oString} {$iString} {$vString}";
    }
    if ($closed) {
        $lastV = [$_v[$i - 1][0] ?? 0, $_v[$i - 1][1] ?? 0];
        $firstV = [$_v[0][0] ?? 0, $_v[0][1] ?? 0];

        $oVector = [
            $lastV[0] + ($_o[$i - 1][0] ?? 0),
            $lastV[1] + ($_o[$i - 1][1] ?? 0),
        ];
        $oString = $mat->applyToPointStringified(...$oVector);
        $iVector = [
            $firstV[0] + ($_i[0][0] ?? 0),
            $firstV[1] + ($_i[0][1] ?? 0),
        ];
        $iString = $mat->applyToPointStringified(...$iVector);
        $vString = $mat->applyToPointStringified(...$firstV);

        $shapeString .= " C{$oString} {$iString} {$vString}z";
    }

    return $shapeString;
}
