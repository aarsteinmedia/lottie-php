<?php

namespace LottiePHP\Utils\Shapes\Modifiers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\PolynomialBezier;
use LottiePHP\Utils\Pooling\ShapePool;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\ShapePath;

use function LottiePHP\Utils\floatEqual;
use function LottiePHP\Utils\floatZero;
use function LottiePHP\Utils\pointEqual;

class OffsetPathModifier extends ShapeModifier
{
    public ?ValueProperty $amount;
    public ?int $lineJoin;
    public ?ValueProperty $miterLimit;

    private function crossProduct(array $a, array $b)
    {
        return [
            $a[1] ?? 0 * ($b[2] ?? 0) - ($a[2] ?? 0) * ($b[1] ?? 0),
            $a[2] ?? 0 * ($b[0] ?? 0) - ($a[0] ?? 0) * ($b[2] ?? 0),
            $a[0] ?? 0 * ($b[1] ?? 0) - ($a[1] ?? 0) * ($b[0] ?? 0),
        ];
    }

    private function getIntersection(PolynomialBezier $a, PolynomialBezier $b)
    {
        $intersect = $a->intersections($b);

        if (count($intersect) > 0 && floatEqual($intersect[0][0] ?? 0, 1)) {
            array_shift($intersect);
        }

        if (count($intersect) > 0) {
            return $intersect[0];
        }

        return null;
    }

    private function joinLines(
        ShapePath $outputBezier,
        PolynomialBezier $seg1,
        PolynomialBezier $seg2,
        int $lineJoin,
        int $miterLimit
    ) {
        $p0 = $seg1->points[3];
        $p1 = $seg2->points[0];

        // Bevel
        if (3 === $lineJoin) {
            return $p0;
        }

        // Connected, they don't need a joint
        if (pointEqual($p0, $p1)) {
            return $p0;
        }

        // Round
        if (2 === $lineJoin) {
            $angleOut = -$seg1->tangentAngle(1);
            $angleIn = -$seg2->tangentAngle(0) + M_PI;
            $center = $this->lineIntersection(
                $p0,
                $this->polarOffset(
                    $p0,
                    $angleOut + M_PI / 2,
                    100
                ),
                $p1,
                $this->polarOffset(
                    $p1,
                    $angleOut + M_PI / 2,
                    100
                )
            );
            $radius = $center ? $this->pointDistance($center, $p0) : $this->pointDistance($p0, $p1) / 2;

            $tan = $this->polarOffset(
                $p0,
                $angleOut,
                2 * $radius * Constants::$roundCorner
            );

            $outputBezier->setXYAt(
                $tan[0],
                $tan[1],
                'o',
                $outputBezier->length() - 1
            );

            $tan = $this->polarOffset(
                $p1,
                $angleIn,
                2 * $radius * Constants::$roundCorner
            );
            $outputBezier->setTripleAt(
                $p1[0],
                $p1[1],
                $p1[0],
                $p1[1],
                $tan[0],
                $tan[1],
                $outputBezier->length()
            );

            return $p1;
        }

        // Miter
        $t0 = pointEqual($p0, $seg1->points[2]) ? $seg1->points[0] : $seg1->points[2];
        $t1 = pointEqual($p1, $seg2->points[1]) ? $seg2->points[3] : $seg2->points[1];
        $intersection = $this->lineIntersection(
            $t0,
            $p0,
            $p1,
            $t1
        );

        if ($intersection && $this->pointDistance($intersection, $p0) < $miterLimit) {
            $outputBezier->setTripleAt(
                $intersection[0],
                $intersection[1],
                $intersection[0],
                $intersection[1],
                $intersection[0],
                $intersection[1],
                $outputBezier->length()
            );

            return $intersection;
        }

        return $p0;
    }

    private function linearOffset(
        array $p1,
        array $p2,
        int $amount
    ) {
        $angle = atan2($p2[0] - $p1[0], $p2[1] - $p1[1]);

        return [$this->polarOffset(
            $p1,
            $angle,
            $amount
        ), $this->polarOffset(
            $p2,
            $angle,
            $amount
        )];
    }

    private function lineIntersection(
        array $start1,
        array $end1,
        array $start2,
        array $end2
    ) {
        $v1 = [$start1[0],
            $start1[1],
            1];
        $v2 = [$end1[0],
            $end1[1],
            1];
        $v3 = [$start2[0],
            $start2[1],
            1];
        $v4 = [$end2[0],
            $end2[1],
            1];
        $r = $this->crossProduct($this->crossProduct($v1, $v2), $this->crossProduct($v3, $v4));

        if (floatZero($r[2] ?? 0)) {
            return null;
        }

        return [$r[0] ?? 0 / ($r[2] ?? 0), $r[1] ?? 0 / ($r[2] ?? 0)];
    }

    private function offsetSegment(PolynomialBezier $segment, int $amount)
    {
        $e = null;

        $e = $this->linearOffset(
            $segment->points[0],
            $segment->points[1],
            $amount
        );
        $p0 = $e[0];
        $p1a = $e[1];

        $e = $this->linearOffset(
            $segment->points[1],
            $segment->points[2],
            $amount
        );
        $p1b = $e[0];
        $p2b = $e[1];

        $e = $this->linearOffset(
            $segment->points[2],
            $segment->points[3],
            $amount
        );
        $p2a = $e[0];
        $p3 = $e[1];
        $p1 = $this->lineIntersection(
            $p0,
            $p1a,
            $p1b,
            $p2b
        );

        $p1 ??= $p1a;
        $p2 = $this->lineIntersection(
            $p2a,
            $p3,
            $p1b,
            $p2b
        );

        $p2 ??= $p2a;

        return new PolynomialBezier(
            $p0,
            $p1,
            $p2,
            $p3
        );
    }

    private function offsetSegmentSplit(PolynomialBezier $segment, int $amount)
    {
        /*
          We split each bezier segment into smaller pieces based
          on inflection points, $this ensures the control point
          polygon is convex.

          (A cubic bezier can have none, one, or two inflection points)
        */
        $flex = $segment->inflectionPoints();
        $left = null;
        $right = null;
        $split = null;

        if (0 === count($flex)) {
            return [$this->offsetSegment($segment, $amount)];
        }

        if (1 === count($flex) || floatEqual($flex[1] ?? 0, 1)) {
            $split = $segment->split($flex[0] ?? 0);

            $left = $split[0];
            $right = $split[1];

            return [$this->offsetSegment($left, $amount), $this->offsetSegment($right, $amount)];
        }

        $split = $segment->split($flex[0] ?? 0);
        $left = $split[0];
        $t = ($flex[1] ?? 0 - ($flex[0] ?? 0)) / (1 - ($flex[0] ?? 0));

        $split = $split[1]?->split($t);
        $mid = $split[0];

        $right = $split[1];

        return [
            $this->offsetSegment($left, $amount),
            $this->offsetSegment($mid, $amount),
            $this->offsetSegment($right, $amount),
        ];
    }

    private function pointDistance(array $p1, array $p2)
    {
        return hypot($p1[0] - $p2[0], $p1[1] - $p2[1]);
    }

    private function polarOffset(
        array $p,
        int $angle,
        int $length
    ) {
        return [
            $p[0] + cos($angle) * $length, $p[1] - sin($angle) * $length,
        ];
    }

    private function pruneIntersections(array $segments)
    {
        $e = null;

        for ($i = 1; $i < count($segments); ++$i) {
            $e = $this->pruneSegmentIntersection($segments[$i - 1], $segments[$i]);
            $segments[$i - 1] = $e[0];
            $segments[$i] = $e[1];
        }

        if (count($segments) > 1) {
            $e = $this->pruneSegmentIntersection($segments[count($segments) - 1], $segments[0]);
            $segments[count($segments) - 1] = $e[0];
            $segments[0] = $e[1];
        }

        return $segments;
    }

    private function pruneSegmentIntersection(array $a, array $b)
    {
        $outa = [...$a];
        $outb = [...$b];
        $intersect = $this->getIntersection($a[count($a) - 1], $b[0]);

        if ($intersect) {
            $outa[count($a) - 1] = explode($intersect[0] ?? 0, $a[count($a) - 1])[0];
            $outb[0] = explode($intersect[1] ?? 0, $b[0])[1];
        }
        if (count($a) > 1 && count($b) > 1) {
            $intersect = $this->getIntersection($a[0], $b[count($b) - 1]);
            if ($intersect) {
                return [
                    [explode($intersect[0] ?? 0, $a[0])[0]], [explode($intersect[1] ?? 0, $b[count($b) - 1])[1]],
                ];
            }
        }

        return [$outa, $outb];
    }

    public function getValue(...$args)
    {
        return $this->processKeys(...$args);
    }

    public function initModifierProperties(
        BaseElement|DynamicPropertyContainer $elem,
        array $data
    ) {
        $this->amount = PropertyFactory::getProp(
            $elem,
            $data['a'],
            0,
            null,
            $this
        );
        $this->miterLimit = PropertyFactory::getProp(
            $elem,
            $data['ml'],
            0,
            null,
            $this
        );
        $this->lineJoin = $data['lj'];
        $this->_isAnimated = count($this->amount['effectsSequence']) > 0;
    }

    public function processPath(
        ShapePath $inputBezier,
        int $amount,
        int $lineJoin,
        int $miterLimit
    ) {
        $outputBezier = ShapePool::newElement();

        $outputBezier->c = $inputBezier->c;
        $count = $inputBezier->length();

        if (!$inputBezier->c) {
            --$count;
        }
        $segment = null;
        $multiSegments = [];

        for ($i = 0; $i < $count; ++$i) {
            $segment = PolynomialBezier::shapeSegment($inputBezier, $i);
            array_push($multiSegments, $this->offsetSegmentSplit($segment, $amount));
        }

        if (!$inputBezier->c) {
            for ($i = $count - 1; $i >= 0; --$i) {
                $segment = PolynomialBezier::shapeSegmentInverted($inputBezier, $i);
                array_push($multiSegments, $this->offsetSegmentSplit($segment, $amount));
            }
        }

        $multiSegments = $this->pruneIntersections($multiSegments);

        // Add bezier segments to the output and apply line joints
        $lastPoint = null;
        $lastSeg = null;

        $length = count($multiSegments);

        for ($i = 0; $i < $length; ++$i) {
            $multiSegment = $multiSegments[$i];

            if ($lastSeg) {
                $lastPoint = $this->joinLines(
                    $outputBezier,
                    $lastSeg,
                    $multiSegment[0],
                    $lineJoin,
                    $miterLimit
                );
            }

            $lastSeg = $multiSegment[count($multiSegment) - 1];

            $mLength = count($multiSegment);

            for ($j = 0; $j < $mLength; ++$j) {
                $segment = $multiSegment[$j];

                if ($lastPoint && pointEqual($segment->points[0], $lastPoint)) {
                    $outputBezier->setXYAt(
                        $segment->points[1][0],
                        $segment->points[1][1],
                        'o',
                        $outputBezier->length() - 1
                    );
                } else {
                    $outputBezier->setTripleAt(
                        $segment->points[0][0],
                        $segment->points[0][1],
                        $segment->points[1][0],
                        $segment->points[1][1],
                        $segment->points[0][0],
                        $segment->points[0][1],
                        $outputBezier->length()
                    );
                }

                $outputBezier->setTripleAt(
                    $segment->points[3][0],
                    $segment->points[3][1],
                    $segment->points[3][0],
                    $segment->points[3][1],
                    $segment->points[2][0],
                    $segment->points[2][1],
                    $outputBezier->length()
                );

                $lastPoint = $segment->points[3];
            }
        }

        if ($length > 0 && $lastSeg) {
            $this->joinLines(
                $outputBezier,
                $lastSeg,
                $multiSegments[0][0] ?? null,
                $lineJoin,
                $miterLimit
            );
        }

        return $outputBezier;
    }

    public function processShapes(bool $_isFirstFrame)
    {
        $length = count($this->shapes);
        $amount = (float) $this->amount?->v;
        $miterLimit = (float) $this->miterLimit?->v;
        $lineJoin = (float) $this->lineJoin;

        if (0 !== $amount) {
            $shapePaths = null;
            $shapeData = null;
            $localShapeCollection = null;

            for ($i = 0; $i < $length; ++$i) {
                $shapeData = $this->shapes[$i];
                $localShapeCollection = $shapeData->localShapeCollection;
                if (!(!$shapeData->shape?->_mdf && !$this->_mdf && !$_isFirstFrame)) {
                    $localShapeCollection?->releaseShapes();
                    if ($shapeData->shape) {
                        $shapeData->shape->_mdf = true;
                    }

                    $shapePaths = $shapeData->shape?->paths?->shapes;
                    $jLen = $shapeData->shape?->paths?->_length || 0;

                    for ($j = 0; $j < $jLen; ++$j) {
                        $shapePath = $shapePaths[$j] ?? null;

                        if (!$shapePath) {
                            continue;
                        }

                        $localShapeCollection?->addShape($this->processPath(
                            $shapePath,
                            $amount,
                            $lineJoin,
                            $miterLimit
                        ));
                    }
                }

                if ($shapeData->shape) {
                    $shapeData->shape->paths = $shapeData->localShapeCollection;
                }
            }
        }
        if (0 === count($this->dynamicProperties)) {
            $this->_mdf = false;
        }
    }
}
