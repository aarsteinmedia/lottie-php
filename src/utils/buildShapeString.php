<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Shapes\ShapePath;

function buildShapeString(
    ShapePath $pathNodes,
    int $length,
    bool $closed,
    Matrix $mat
) {
    if ($length === 0) {
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

    for ($i = 1; $i < $length; $i++) {
        $oVector = [$_o[$i - 1][0] ?? 0, $_o[$i - 1][1] ?? 0];
        $oString = $mat->applyToPointStringified(...$oVector);
        $iVector = [$_i[$i][0] ?? 0, $_i[$i][1] ?? 0];
        $iString = $mat->applyToPointStringified(...$iVector);
        $vVector = [$_v[$i][0] ?? 0, $_v[$i][1] ?? 0];
        $vString = $mat->applyToPointStringified(...$vVector);

        $shapeString += " C{$oString} {$iString} {$vString}";
    }
    if ($closed) {
        $oVector = [$_o[$i - 1][0] ?? 0, $_o[$i - 1][1] ?? 0];
        $oString = $mat->applyToPointStringified(...$oVector);
        $iVector = [$_i[0][0] ?? 0, $_i[0][1] ?? 0];
        $iString = $mat->applyToPointStringified(...$iVector);
        $vVector = [$_v[0][0] ?? 0, $_v[0][1] ?? 0];
        $vString = $mat->applyToPointStringified(...$vVector);

        $shapeString += " C{$oString} {$iString} {$vString}z";
    }

    return $shapeString;
}
