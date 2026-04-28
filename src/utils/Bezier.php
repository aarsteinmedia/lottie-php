<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Helpers\Resolution;
use LottiePHP\Utils\Shapes\ShapePath;

use function LottiePHP\Utils\Pooling\bezierLengthPool;
use function LottiePHP\Utils\Pooling\segmentsLengthPool;

class Bezier
{
    private static function getBezierLength(
        array $pt1,
        array $pt2,
        array $pt3,
        array $pt4
    ): array {
        $curveSegments = Resolution::getDefaultCurveSegments();
        $ptCoord = null;
        $perc = 0;
        $addedLength = 0;
        $ptDistance = null;
        $point = [];
        $lastPoint = [];
        $lengthData = bezierLengthPool()->newElement();
        $len = count($pt3);

        for ($k = 0; $k < $curveSegments; ++$k) {
            $perc = $k / ($curveSegments - 1);
            $ptDistance = 0;
            for ($i = 0; $i < $len; ++$i) {
                $ptCoord
                  = pow(1 - $perc, 3) * ($pt1[$i] ?? 0)
                  + 3 * pow(1 - $perc, 2) * $perc * ($pt3[$i] ?? 0)
                  + 3 * (1 - $perc) * pow($perc, 2) * ($pt4[$i] ?? 0)
                  + pow($perc, 3) * ($pt2[$i] ?? 0);
                $point[$i] = $ptCoord;
                if ('number' === gettype($lastPoint[$i])) {
                    $ptDistance += pow(($point[$i] ?? 0) - ($lastPoint[$i] ?? 0), 2);
                }
                $lastPoint[$i] = $point[$i] ?? 0;
            }
            if ($ptDistance) {
                $ptDistance = sqrt($ptDistance);
                $addedLength += $ptDistance;
            }
            $lengthData['percents'][$k] = $perc;
            $lengthData['lengths'][$k] = $addedLength;
        }
        $lengthData['addedLength'] = $addedLength;

        return $lengthData;
    }

    private static function getDistancePerc(int $perc, $bezierLength)
    {
        [
            'addedLength' => $addedLength,
            'lengths' => $lengths,
            'percents' => $percents,
        ] = $bezierLength;

        $length = count($percents);
        $initPos = floor(($length - 1) * $perc);
        $lengthPos = $perc * $addedLength;
        $lPerc = 0;

        if (
            $initPos === $length - 1
            || 0 === $initPos
            || $lengthPos === $lengths[$initPos]
        ) {
            return $percents[$initPos];
        }
        $dir = ($lengths[$initPos] ?? 0) > $lengthPos ? -1 : 1;
        $shouldIterate = true;

        while ($shouldIterate) {
            if (($lengths[$initPos] ?? 0) <= $lengthPos && ($lengths[$initPos + 1] ?? 0) > $lengthPos) {
                $lPerc
                  = ($lengthPos - ($lengths[$initPos] ?? 0))
                  / (($lengths[$initPos + 1] ?? 0) - ($lengths[$initPos] ?? 0));
                $shouldIterate = false;
            } else {
                $initPos += $dir;
            }
            if ($initPos < 0 || $initPos >= $length - 1) {
                // FIX for TypedArrays that don't store floating point values with enough accuracy
                if ($initPos === $length - 1) {
                    return $percents[$initPos];
                }
                $shouldIterate = false;
            }
        }

        return ($percents[$initPos] ?? 0) + (($percents[$initPos + 1] ?? 0) - ($percents[$initPos] ?? 0)) * $lPerc;
    }

    public static function buildBezierData(
        array $pt1,
        array $pt2,
        array $pt3,
        array $pt4
    ) {
        $storedData = [];
        $bezierName = str_repeat('.', 'p', "{$pt1[0]}_{$pt1[1]}_{$pt2[0]}_{$pt2[1]}_{$pt3[0]}_{$pt3[1]}_{$pt4[0]}_{$pt4[1]}");

        if (!$storedData[$bezierName]) {
            $curveSegments = Resolution::getDefaultCurveSegments();
            $ptCoord = [];
            $perc = 0;
            $addedLength = 0;
            $ptDistance = 0;
            $point = [];
            $lastPoint = null;

            if (
                2 === count($pt1)
                && ($pt1[0] !== $pt2[0] || $pt1[1] !== $pt2[1])
                && self::pointOnLine2D(
                    $pt1[0],
                    $pt1[1],
                    $pt2[0],
                    $pt2[1],
                    $pt1[0] + $pt3[0],
                    $pt1[1] + $pt3[1]
                )
                && self::pointOnLine2D(
                    $pt1[0],
                    $pt1[1],
                    $pt2[0],
                    $pt2[1],
                    $pt2[0] + $pt4[0],
                    $pt2[1] + $pt4[1]
                )
            ) {
                $curveSegments = 2;
            }
            $bezierData = new BezierData($curveSegments);
            $length = count($pt3);

            for ($k = 0; $k < $curveSegments; ++$k) {
                $point = createSizedArray($length);
                $perc = $k / ($curveSegments - 1);
                $ptDistance = 0;
                for ($i = 0; $i < $length; ++$i) {
                    $ptCoord
                      = pow(1 - $perc, 3) * ($pt1[$i] ?? 0)
                      + 3 * pow(1 - $perc, 2) * $perc * (($pt1[$i] ?? 0) + ($pt3[$i] ?? 0))
                      + 3 * (1 - $perc) * pow($perc, 2) * (($pt2[$i] ?? 0) + ($pt4[$i] ?? 0))
                      + pow($perc, 3) * ($pt2[$i] ?? 0);
                    $point[$i] = $ptCoord;
                    if (null !== $lastPoint) {
                        $ptDistance += pow((int) $point[$i] - (int) $lastPoint[$i], 2);
                    }
                }
                $ptDistance = sqrt($ptDistance);
                $addedLength += $ptDistance;
                $bezierData->points[$k] = new PointData($ptDistance, $point);
                $lastPoint = $point;
            }
            $bezierData->segmentLength = $addedLength;
            $storedData[$bezierName] = $bezierData;
        }

        return $storedData[$bezierName];
    }

    public static function getNewSegment(
        array $pt1,
        array $pt2,
        array $pt3,
        array $pt4,
        int $startPercFromProps,
        int $endPercFromProps,
        array $bezierData
    ) {
        if (null === self::$_bezierSegmentPoints) {
            self::$_bezierSegmentPoints = createTypedArray(ArrayType::Float32, 8);
        }

        $startPerc = $startPercFromProps;
        $endPerc = $endPercFromProps;

        if ($startPerc < 0) {
            $startPerc = 0;
        } elseif ($startPerc > 1) {
            $startPerc = 1;
        }
        $t0 = self::getDistancePerc($startPerc, $bezierData) ?? 0;

        $endPerc = $endPerc > 1 ? 1 : $endPerc;
        $t1 = self::getDistancePerc($endPerc, $bezierData) ?? 0;
        $u0 = 1 - $t0;
        $u1 = 1 - $t1;
        $u0u0u0 = $u0 * $u0 * $u0;
        $t0u0u0_3 = $t0 * $u0 * $u0 * 3;
        $t0t0u0_3 = $t0 * $t0 * $u0 * 3;
        $t0t0t0 = $t0 * $t0 * $t0;
        $u0u0u1 = $u0 * $u0 * $u1;
        $t0u0u1_3 = $t0 * $u0 * $u1 + $u0 * $t0 * $u1 + $u0 * $u0 * $t1;
        $t0t0u1_3 = $t0 * $t0 * $u1 + $u0 * $t0 * $t1 + $t0 * $u0 * $t1;
        $t0t0t1 = $t0 * $t0 * $t1;
        $u0u1u1 = $u0 * $u1 * $u1;
        $t0u1u1_3 = $t0 * $u1 * $u1 + $u0 * $t1 * $u1 + $u0 * $u1 * $t1;
        $t0t1u1_3 = $t0 * $t1 * $u1 + $u0 * $t1 * $t1 + $t0 * $u1 * $t1;
        $t0t1t1 = $t0 * $t1 * $t1;
        $u1u1u1 = $u1 * $u1 * $u1;
        $t1u1u1_3 = $t1 * $u1 * $u1 + $u1 * $t1 * $u1 + $u1 * $u1 * $t1;
        $t1t1u1_3 = $t1 * $t1 * $u1 + $u1 * $t1 * $t1 + $t1 * $u1 * $t1;
        $t1t1t1 = $t1 * $t1 * $t1;
        $length = count($pt1);

        for ($i = 0; $i < $length; ++$i) {
            self::$_bezierSegmentPoints[$i * 4]
              = round(($u0u0u0 * ($pt1[$i] ?? 0)
                + $t0u0u0_3 * ($pt3[$i] ?? 0)
                + $t0t0u0_3 * ($pt4[$i] ?? 0)
                + $t0t0t0 * ($pt2[$i] ?? 0))
                * 1000) / 1000;
            self::$_bezierSegmentPoints[$i * 4 + 1]
              = round(($u0u0u1 * ($pt1[$i] ?? 0)
                + $t0u0u1_3 * ($pt3[$i] ?? 0)
                + $t0t0u1_3 * ($pt4[$i] ?? 0)
                + $t0t0t1 * ($pt2[$i] ?? 0))
                * 1000) / 1000;
            self::$_bezierSegmentPoints[$i * 4 + 2]
              = round(($u0u1u1 * ($pt1[$i] ?? 0)
                + $t0u1u1_3 * ($pt3[$i] ?? 0)
                + $t0t1u1_3 * ($pt4[$i] ?? 0)
                + $t0t1t1 * ($pt2[$i] ?? 0))
                * 1000) / 1000;
            self::$_bezierSegmentPoints[$i * 4 + 3]
              = round(($u1u1u1 * ($pt1[$i] ?? 0)
                + $t1u1u1_3 * ($pt3[$i] ?? 0)
                + $t1t1u1_3 * ($pt4[$i] ?? 0)
                + $t1t1t1 * ($pt2[$i] ?? 0))
                * 1000) / 1000;
        }

        return self::$_bezierSegmentPoints;
    }

    public static function getPointInSegment(
        array $pt1,
        array $pt2,
        array $pt3,
        array $pt4,
        int $percent,
        array $bezierData
    ) {
        $t1 = self::getDistancePerc($percent, $bezierData) ?? 0;
        $u1 = 1 - $t1;
        $ptX
          = round(($u1 * $u1 * $u1 * $pt1[0]
            + ($t1 * $u1 * $u1 + $u1 * $t1 * $u1 + $u1 * $u1 * $t1) * $pt3[0]
            + ($t1 * $t1 * $u1 + $u1 * $t1 * $t1 + $t1 * $u1 * $t1) * $pt4[0]
            + $t1 * $t1 * $t1 * $pt2[0])
            * 1000) / 1000;
        $ptY
          = round(($u1 * $u1 * $u1 * $pt1[1]
            + ($t1 * $u1 * $u1 + $u1 * $t1 * $u1 + $u1 * $u1 * $t1) * $pt3[1]
            + ($t1 * $t1 * $u1 + $u1 * $t1 * $t1 + $t1 * $u1 * $t1) * $pt4[1]
            + $t1 * $t1 * $t1 * $pt2[1])
            * 1000) / 1000;

        return [$ptX, $ptY];
    }

    public static function getSegmentsLength(ShapePath $shapeData): array
    {
        $segmentsLength = segmentsLengthPool()->newElement();
        $isClosed = $shapeData->c;
        $pathV = $shapeData->v;
        $pathO = $shapeData->o;
        $pathI = $shapeData->i;
        $i = 0;
        $len = $shapeData->_length || 0;
        ['lengths' => $lengths] = $segmentsLength;
        $totalLength = 0;

        for ($i = 0; $i < $len - 1; ++$i) {
            $lengths[$i] = self::getBezierLength(
                $pathV[$i],
                $pathV[$i + 1],
                $pathO[$i],
                $pathI[$i + 1]
            );
            $totalLength += $lengths[$i]['addedLength'] ?? 0;
        }
        if ($isClosed && $len) {
            $lengths[$i] = self::getBezierLength(
                $pathV[$i],
                $pathV[0],
                $pathO[$i],
                $pathI[0]
            );
            $totalLength += $lengths[$i]['addedLength'] ?? 0;
        }
        $segmentsLength['totalLength'] = $totalLength;

        return $segmentsLength;
    }

    public static function pointOnLine2D(
        int $x1,
        int $y1,
        int $x2,
        int $y2,
        int $x3,
        int $y3
    ) {
        $det1 = $x1 * $y2 + $y1 * $x3 + $x2 * $y3 - $x3 * $y2 - $y3 * $x1 - $x2 * $y1;

        return $det1 > -0.001 && $det1 < 0.001;
    }

    public static function pointOnLine3D(
        int $x1,
        int $y1,
        int $z1,
        int $x2,
        int $y2,
        int $z2,
        int $x3,
        int $y3,
        int $z3
    ) {
        if (0 === $z1 && 0 === $z2 && 0 === $z3) {
            return self::pointOnLine2D(
                $x1,
                $y1,
                $x2,
                $y2,
                $x3,
                $y3
            );
        }
        $dist1 = sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2) + pow($z2 - $z1, 2));
        $dist2 = sqrt(pow($x3 - $x1, 2) + pow($y3 - $y1, 2) + pow($z3 - $z1, 2));
        $dist3 = sqrt(pow($x3 - $x2, 2) + pow($y3 - $y2, 2) + pow($z3 - $z2, 2));
        $diffDist = 0;

        if ($dist1 > $dist2) {
            if ($dist1 > $dist3) {
                $diffDist = $dist1 - $dist2 - $dist3;
            } else {
                $diffDist = $dist3 - $dist2 - $dist1;
            }
        } elseif ($dist3 > $dist2) {
            $diffDist = $dist3 - $dist2 - $dist1;
        } else {
            $diffDist = $dist2 - $dist1 - $dist3;
        }

        return $diffDist > -0.0001 && $diffDist < 0.0001;
    }
    private static $_bezierSegmentPoints;
}

class BezierData
{
    public array $points;
    public $segmentLength = 0;

    public function __construct(int $length)
    {
        $this->points = createSizedArray($length);
    }
}

class PointData
{
    public int $partialLength;
    public array $point;

    public function __construct(int $partial, array $point)
    {
        $this->partialLength = $partial;
        $this->point = $point;
    }
}

// $Bezier = {
//   buildBezierData,
//   getNewSegment,
//   getPointInSegment,
//   getSegmentsLength,
//   pointOnLine2D,
//   pointOnLine3D,
// }
