<?php

namespace LottiePHP\Utils\Shapes\Modifiers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Pooling\ShapePool;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\ShapePath;

class PuckerAndBloatModifier extends ShapeModifier
{
    public ?ValueProperty $amount;

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
        $this->_isAnimated = count($this->amount->effectsSequence) > 0;
    }

    public function processPath(ShapePath $path, int $amount)
    {
        $percent = $amount / 100;
        $centerPoint = [0, 0];
        $pathLength = $path->_length;
        $i = 0;

        for ($i = 0; $i < $pathLength; ++$i) {
            $centerPoint[0] += $path->v[$i][0] ?? 0;
            $centerPoint[1] += $path->v[$i][1] ?? 0;
        }
        $centerPoint[0] /= $pathLength;
        $centerPoint[1] /= $pathLength;
        $clonedPath = ShapePool::newElement();

        $clonedPath->c = $path->c;
        $vX = 0;
        $vY = 0;
        $oX = 0;
        $oY = 0;
        $iX = 0;
        $iY = 0;

        for ($i = 0; $i < $pathLength; ++$i) {
            $vX = $path->v[$i][0] ?? 0 + ($centerPoint[0] ?? 0 - ($path->v[$i][0] ?? 0)) * $percent;
            $vY = $path->v[$i][1] ?? 0 + ($centerPoint[1] ?? 0 - ($path->v[$i][1] ?? 0)) * $percent;
            $oX = $path->o[$i][0] ?? 0 + ($centerPoint[0] ?? 0 - ($path->o[$i][0] ?? 0)) * -$percent;
            $oY = $path->o[$i][1] ?? 0 + ($centerPoint[1] ?? 0 - ($path->o[$i][1] ?? 0)) * -$percent;
            $iX = $path->i[$i][0] ?? 0 + ($centerPoint[0] ?? 0 - ($path->i[$i][0] ?? 0)) * -$percent;
            $iY = $path->i[$i][1] ?? 0 + ($centerPoint[1] ?? 0 - ($path->i[$i][1] ?? 0)) * -$percent;
            $clonedPath->setTripleAt(
                $vX,
                $vY,
                $oX,
                $oY,
                $iX,
                $iY,
                $i
            );
        }

        return $clonedPath;
    }

    public function processShapes(bool $_isFirstFrame)
    {
        $length = count($this->shapes);
        $amount = $this->amount?->v;

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
                    $jLen = $shapeData->shape?->paths?->_length ?? 0;

                    for ($j = 0; $j < $jLen; ++$j) {
                        $shapePath = $shapePaths[$j] ?? null;

                        if ($shapePath) {
                            $localShapeCollection?->addShape($this->processPath($shapePath, $amount || 0));
                        }
                    }
                }
                if ($shapeData->localShapeCollection && $shapeData->shape) {
                    $shapeData->shape->paths = $shapeData->localShapeCollection;
                }
            }
        }
        if (0 === count($this->dynamicProperties)) {
            $this->_mdf = false;
        }
    }
}
