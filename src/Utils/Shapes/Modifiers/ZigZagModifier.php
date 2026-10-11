<?php

namespace LottiePHP\Utils\Shapes\Modifiers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\PolynomialBezier;
use LottiePHP\Utils\Pooling\ShapePool;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\ShapePath;

class ZigZagModifier extends ShapeModifier
{
    public ?ValueProperty $amplitude;
    public ?ValueProperty $frequency;
    public ?ValueProperty $pointsType;

    private static function getPerpendicularVector(array $pt1, array $pt2)
    {
        $vector = [$pt2[0] - $pt1[0], $pt2[1] - $pt1[1]];
        $rot = -M_PI * 0.5;

        return [
            cos($rot) * ($vector[0] ?? 0) - sin($rot) * ($vector[1] ?? 0), sin($rot) * ($vector[0] ?? 0) + cos($rot) * ($vector[1] ?? 0),
        ];
    }

    private static function getProjectingAngle(ShapePath $path, int $cur)
    {
        $len = $path->length();
        $prevIndex = 0 === $cur ? $len - 1 : $cur - 1;
        $nextIndex = ($cur + 1) % $len;
        $prevPoint = $path->v[$prevIndex];
        $nextPoint = $path->v[$nextIndex];
        $pVector = self::getPerpendicularVector($prevPoint, $nextPoint);

        return atan2(0, 1) - atan2($pVector[1], $pVector[0]);
    }

    private static function setPoint(
        ShapePath $outputBezier,
        array $point,
        float $angle,
        int $direction,
        float $amplitude,
        float $outAmplitude,
        float $inAmplitude
    ) {
        $angO = $angle - M_PI / 2;
        $angI = $angle + M_PI / 2;
        $px = $point[0] + cos($angle) * $direction * $amplitude;
        $py = $point[1] - sin($angle) * $direction * $amplitude;
        $oX = $px + cos($angO) * $outAmplitude;
        $oY = $py - sin($angO) * $outAmplitude;
        $iX = $px + cos($angI) * $inAmplitude;
        $iY = $py - sin($angI) * $inAmplitude;

        // Match aarsteinmedia/lottie-web control geometry; store relative tangents for buildShapeString.
        $outputBezier->setTripleAt(
            $px,
            $py,
            $oX - $px,
            $oY - $py,
            $iX - $px,
            $iY - $py,
            $outputBezier->length()
        );
    }

    private static function zigZagCorner(
        ShapePath $outputBezier,
        ShapePath $path,
        int $cur,
        float $amplitude,
        int $frequency,
        int $pointType,
        int $direction
    ) {
        $angle = self::getProjectingAngle($path, $cur);
        $point = $path->v[$cur % $path->_length];
        $prevPoint = $path->v[0 === $cur ? $path->_length - 1 : $cur - 1];
        $nextPoint = $path->v[($cur + 1) % $path->_length];
        $prevDist
              = 2 === $pointType
                ? sqrt(pow($point[0] - $prevPoint[0], 2)
                  + pow($point[1] - $prevPoint[1], 2))
                : 0;
        $nextDist
              = 2 === $pointType
                ? sqrt(pow($point[0] - $nextPoint[0], 2)
                  + pow($point[1] - $nextPoint[1], 2))
                : 0;

        self::setPoint(
            $outputBezier,
            $path->v[$cur % $path->_length] ?? [0, 0],
            $angle,
            $direction,
            $amplitude,
            $nextDist / (($frequency + 1) * 2),
            $prevDist / (($frequency + 1) * 2)
        );
    }

    private static function zigZagSegment(
        ShapePath $outputBezier,
        PolynomialBezier $segment,
        float $amplitude,
        int $frequency,
        int $pointType,
        int $directionFromProps
    ) {
        $direction = $directionFromProps;

        for ($i = 0; $i < $frequency; ++$i) {
            $t = ($i + 1) / ($frequency + 1);
            $dist
                  = 2 === $pointType
                    ? sqrt(pow($segment->points[3][0] - $segment->points[0][0], 2)
                      + pow($segment->points[3][1] - $segment->points[0][1], 2))
                    : 0;
            $angle = $segment->normalAngle($t);
            $point = $segment->point($t);

            self::setPoint(
                $outputBezier,
                $point,
                $angle,
                $direction,
                $amplitude,
                $dist / (($frequency + 1) * 2),
                $dist / (($frequency + 1) * 2)
            );

            $direction = -$direction;
        }

        return $direction;
    }

    public function getValue(?bool $flag = null)
    {
        return $this->processKeys($flag);
    }

    public function initModifierProperties(
        BaseElement|DynamicPropertyContainer $elem,
        array $data
    ) {
        $this->amplitude = PropertyFactory::getProp(
            $elem,
            $data['s'],
            0,
            null,
            $this
        );
        $this->frequency = PropertyFactory::getProp(
            $elem,
            $data['r'],
            0,
            null,
            $this
        );
        $this->pointsType = PropertyFactory::getProp(
            $elem,
            $data['pt'],
            0,
            null,
            $this
        );
        $this->_isAnimated
          = count($this->amplitude->effectsSequence) > 0
          || count($this->frequency->effectsSequence) > 0
          || count($this->pointsType->effectsSequence) > 0;
    }

    public function processPath(
        ShapePath $path,
        float $amplitude,
        int $frequency,
        int $pointType
    ) {
        $count = $path->_length;
        $clonedPath = ShapePool::newElement();

        $clonedPath->c = $path->c;

        if (!$path->c) {
            --$count;
        }

        if (0 === $count) {
            return $clonedPath;
        }

        $direction = -1;
        $segment = PolynomialBezier::shapeSegment($path, 0);

        self::zigZagCorner(
            $clonedPath,
            $path,
            0,
            $amplitude,
            $frequency,
            $pointType,
            $direction
        );

        for ($i = 0; $i < $count; ++$i) {
            $direction = self::zigZagSegment(
                $clonedPath,
                $segment,
                $amplitude,
                $frequency,
                $pointType,
                -$direction
            );

            if ($i === $count - 1 && !$path->c) {
                $segment = null;
            } else {
                $segment = PolynomialBezier::shapeSegment($path, ($i + 1) % $count);
            }

            self::zigZagCorner(
                $clonedPath,
                $path,
                $i + 1,
                $amplitude,
                $frequency,
                $pointType,
                $direction
            );
        }

        return $clonedPath;
    }

    public function processShapes(bool $_isFirstFrame)
    {
        $amplitude = (float) ($this->amplitude?->v ?? 0);
        $frequency = max(0, (int) round((float) ($this->frequency?->v ?? 0)));
        $pointType = (int) ($this->pointsType?->v ?? 1);

        if (0 != $amplitude) {
            $shapeData = null;
            $localShapeCollection = null;
            $shapePaths = null;
            $length = count($this->shapes);

            for ($i = 0; $i < $length; ++$i) {
                $shapeData = $this->shapes[$i];
                $localShapeCollection = $shapeData->localShapeCollection;
                if (!(!$shapeData->shape?->_mdf && !$this->_mdf && !$_isFirstFrame)) {
                    $localShapeCollection?->releaseShapes();
                    if ($shapeData->shape) {
                        $shapeData->shape->_mdf = true;
                    }

                    $shapePaths = $shapeData->shape?->paths?->shapes ?? [];
                    $_length = $shapeData->shape?->paths->_length ?? 0;

                    for ($j = 0; $j < $_length; ++$j) {
                        $localShapeCollection?->addShape($this->processPath(
                            $shapePaths[$j],
                            $amplitude,
                            $frequency,
                            $pointType
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
