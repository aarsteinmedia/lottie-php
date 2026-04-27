<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Shapes\ShapePath;

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
