<?php

namespace LottiePHP\Utils\Shapes\Modifiers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Pooling\ShapePool;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\ShapePath;

class RoundCornersModifier extends ShapeModifier
{
    public ?ValueProperty $rd;

    public function getValue(...$args)
    {
        return $this->processKeys(...$args);
    }

    public function initModifierProperties(
        BaseElement|DynamicPropertyContainer $elem,
        array $data
    ) {
        $this->rd = PropertyFactory::getProp(
            $elem,
            $data['r'],
            0,
            null,
            $this
        );
        $this->_isAnimated = count($this->rd->effectsSequence) > 0;
    }

    public function processPath(ShapePath $path, float $round)
    {
        $clonedPath = ShapePool::newElement();

        $clonedPath->c = $path->c;
        $len = $path->_length;
        $currentV = null;
        $currentI = null;
        $currentO = null;
        $closerV = null;
        $distance = null;
        $newPosPerc = null;
        $index = 0;
        $vX = null;
        $vY = null;
        $oX = null;
        $oY = null;
        $iX = null;
        $iY = null;

        for ($i = 0; $i < $len; ++$i) {
            $currentV = $path->v[$i];
            $currentO = $path->o[$i];
            $currentI = $path->i[$i];
            if (
                ($currentV[0] ?? null) === $currentO[0]
                && $currentV[1] === $currentO[1]
                && $currentV[0] === $currentI[0]
                && $currentV[1] === $currentI[1]
            ) {
                if ((0 === $i || $i === $len - 1) && !$path->c) {
                    $clonedPath->setTripleAt(
                        $currentV[0],
                        $currentV[1],
                        $currentO[0],
                        $currentO[1],
                        $currentI[0],
                        $currentI[1],
                        $index
                    );
                    /* clonedPath.v[index] = currentV;
                          clonedPath.o[index] = currentO;
                          clonedPath.i[index] = currentI; */
                    ++$index;
                } else {
                    if (0 === $i) {
                        $closerV = $path->v[$len - 1];
                    } else {
                        $closerV = $path->v[$i - 1];
                    }
                    $distance = sqrt(pow($currentV[0] - $closerV[0], 2)
                      + pow($currentV[1] - $closerV[1], 2));
                    $newPosPerc = $distance ? min($distance / 2, $round) / $distance : 0;
                    $iX = $currentV[0] + ($closerV[0] - $currentV[0]) * $newPosPerc;
                    $vX = $iX;
                    $iY = $currentV[1] - ($currentV[1] - $closerV[1]) * $newPosPerc;
                    $vY = $iY;
                    $oX = $vX - ($vX - $currentV[0]) * Constants::$roundCorner;
                    $oY = $vY - ($vY - $currentV[1]) * Constants::$roundCorner;
                    $clonedPath->setTripleAt(
                        $vX,
                        $vY,
                        $oX,
                        $oY,
                        $iX,
                        $iY,
                        $index
                    );
                    ++$index;

                    if ($i === $len - 1) {
                        $closerV = $path->v[0];
                    } else {
                        $closerV = $path->v[$i + 1];
                    }
                    $distance = sqrt(pow($currentV[0] - $closerV[0], 2)
                      + pow($currentV[1] - $closerV[1], 2));
                    $newPosPerc = $distance ? min($distance / 2, $round) / $distance : 0;
                    $oX = $currentV[0] + ($closerV[0] - $currentV[0]) * $newPosPerc;
                    $vX = $oX;
                    $oY = $currentV[1] + ($closerV[1] - $currentV[1]) * $newPosPerc;
                    $vY = $oY;
                    $iX = $vX - ($vX - $currentV[0]) * Constants::$roundCorner;
                    $iY = $vY - ($vY - $currentV[1]) * Constants::$roundCorner;
                    $clonedPath->setTripleAt(
                        $vX,
                        $vY,
                        $oX,
                        $oY,
                        $iX,
                        $iY,
                        $index
                    );
                    ++$index;
                }
            } else {
                $clonedPath->setTripleAt(
                    $path->v[$i][0] ?? 0,
                    $path->v[$i][1] ?? 0,
                    $path->o[$i][0] ?? 0,
                    $path->o[$i][1] ?? 0,
                    $path->i[$i][0] ?? 0,
                    $path->i[$i][1] ?? 0,
                    $index
                );
                ++$index;
            }
        }

        return $clonedPath;
    }

    public function processShapes(bool $_isFirstFrame)
    {
        $length = count($this->shapes);
        $rd = $this->rd?->v;

        if (0 !== $rd) {
            $shapeData = null;
            $shapePaths = null;
            $localShapeCollection = null;

            for ($i = 0; $i < $length; ++$i) {
                $shapeData = $this->shapes[$i];
                $localShapeCollection = $shapeData->localShapeCollection;
                if (!(!$shapeData->shape?->_mdf && !$this->_mdf && !$_isFirstFrame)) {
                    $localShapeCollection?->releaseShapes();
                    if ($shapeData->shape) {
                        $shapeData->shape->_mdf = true;
                    }

                    $shapePaths = $shapeData->shape?->paths?->shapes ?? [];
                    $jLen = $shapeData->shape?->paths?->_length ?? 0;

                    for ($j = 0; $j < $jLen; ++$j) {
                        $localShapeCollection?->addShape($this->processPath($shapePaths[$j], $rd));
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
