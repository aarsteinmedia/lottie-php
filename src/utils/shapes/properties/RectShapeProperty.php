<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Pooling\ShapeCollectionPool;
use LottiePHP\Utils\Pooling\ShapePool;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\Properties\KeyframedMultidimensionalProperty;
use LottiePHP\Utils\Properties\MultiDimensionalProperty;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;

class RectShapeProperty extends ShapeBaseProperty
{
    public ?int $d;
    public ?ValueProperty $ir;
    public ?ValueProperty $is;
    public ?ValueProperty $or;
    public ?ValueProperty $os;
    public ?ValueProperty $pt;
    public ValueProperty $r;
    public KeyframedMultidimensionalProperty|MultiDimensionalProperty $s;

    // ShapePath stores in/out tangents as relative vectors (Lottie JSON semantics),
    // not absolute positions. buildShapeString() will add them to vertices.
    private function setTripleAtRelative(
        float $vX,
        float $vY,
        float $oXAbs,
        float $oYAbs,
        float $iXAbs,
        float $iYAbs,
        int $pos,
        bool $replace = true
    ): void {
        $this->v?->setTripleAt(
            $vX,
            $vY,
            $oXAbs - $vX,
            $oYAbs - $vY,
            $iXAbs - $vX,
            $iYAbs - $vY,
            $pos,
            $replace
        );
    }

    public function __construct(BaseElement|BaseProperty $elem, array $data)
    {
        $this->v = ShapePool::newElement();
        $this->v->c = true;
        $this->localShapeCollection = ShapeCollectionPool::newShapeCollection();
        $this->localShapeCollection->addShape($this->v);
        $this->paths = $this->localShapeCollection;
        $this->elem = $elem;
        $this->comp = $elem->comp;
        $this->frameId = -1;
        $this->d = $data['d'] ?? null;
        $this->initDynamicPropertyContainer($elem);
        $this->p = PropertyFactory::getProp(
            $elem,
            $data['p'],
            1,
            0,
            $this
        );
        $this->s = PropertyFactory::getProp(
            $elem,
            $data['s'],
            1,
            0,
            $this
        );
        $this->r = PropertyFactory::getProp(
            $elem,
            $data['r'],
            0,
            0,
            $this
        );
        if (count($this->dynamicProperties) > 0) {
            $this->k = true;
        } else {
            $this->k = false;
            $this->convertRectToPath();
        }
    }

    public function convertRectToPath()
    {
        if (!$this->p) {
            throw new \Error(get_class($this).': p value is not implemented');
        }

        $p0 = $this->p->v[0];
        $p1 = $this->p->v[1];
        $v0 = $this->s->v[0] / 2;
        $v1 = $this->s->v[1] / 2;
        $round = min(
            $v0,
            $v1,
            $this->r->v
        );
        $cPoint = $round * (1 - Constants::$roundCorner);

        if ($this->v) {
            $this->v->_length = 0;
        }

        if (2 === $this->d || 1 === $this->d) {
            $this->setTripleAtRelative(
                $p0 + $v0,
                $p1 - $v1 + $round,
                $p0 + $v0,
                $p1 - $v1 + $round,
                $p0 + $v0,
                $p1 - $v1 + $cPoint,
                0
            );
            $this->setTripleAtRelative(
                $p0 + $v0,
                $p1 + $v1 - $round,
                $p0 + $v0,
                $p1 + $v1 - $cPoint,
                $p0 + $v0,
                $p1 + $v1 - $round,
                1
            );
            if (0 === $round) {
                $this->setTripleAtRelative(
                    $p0 - $v0,
                    $p1 + $v1,
                    $p0 - $v0 + $cPoint,
                    $p1 + $v1,
                    $p0 - $v0,
                    $p1 + $v1,
                    2
                );
                $this->setTripleAtRelative(
                    $p0 - $v0,
                    $p1 - $v1,
                    $p0 - $v0,
                    $p1 - $v1 + $cPoint,
                    $p0 - $v0,
                    $p1 - $v1,
                    3
                );
            } else {
                $this->setTripleAtRelative(
                    $p0 + $v0 - $round,
                    $p1 + $v1,
                    $p0 + $v0 - $round,
                    $p1 + $v1,
                    $p0 + $v0 - $cPoint,
                    $p1 + $v1,
                    2
                );
                $this->setTripleAtRelative(
                    $p0 - $v0 + $round,
                    $p1 + $v1,
                    $p0 - $v0 + $cPoint,
                    $p1 + $v1,
                    $p0 - $v0 + $round,
                    $p1 + $v1,
                    3
                );
                $this->setTripleAtRelative(
                    $p0 - $v0,
                    $p1 + $v1 - $round,
                    $p0 - $v0,
                    $p1 + $v1 - $round,
                    $p0 - $v0,
                    $p1 + $v1 - $cPoint,
                    4
                );
                $this->setTripleAtRelative(
                    $p0 - $v0,
                    $p1 - $v1 + $round,
                    $p0 - $v0,
                    $p1 - $v1 + $cPoint,
                    $p0 - $v0,
                    $p1 - $v1 + $round,
                    5
                );
                $this->setTripleAtRelative(
                    $p0 - $v0 + $round,
                    $p1 - $v1,
                    $p0 - $v0 + $round,
                    $p1 - $v1,
                    $p0 - $v0 + $cPoint,
                    $p1 - $v1,
                    6
                );
                $this->setTripleAtRelative(
                    $p0 + $v0 - $round,
                    $p1 - $v1,
                    $p0 + $v0 - $cPoint,
                    $p1 - $v1,
                    $p0 + $v0 - $round,
                    $p1 - $v1,
                    7
                );
            }
        } else {
            $this->setTripleAtRelative(
                $p0 + $v0,
                $p1 - $v1 + $round,
                $p0 + $v0,
                $p1 - $v1 + $cPoint,
                $p0 + $v0,
                $p1 - $v1 + $round,
                0
            );
            if (0 === $round) {
                $this->setTripleAtRelative(
                    $p0 - $v0,
                    $p1 - $v1,
                    $p0 - $v0 + $cPoint,
                    $p1 - $v1,
                    $p0 - $v0,
                    $p1 - $v1,
                    1
                );
                $this->setTripleAtRelative(
                    $p0 - $v0,
                    $p1 + $v1,
                    $p0 - $v0,
                    $p1 + $v1 - $cPoint,
                    $p0 - $v0,
                    $p1 + $v1,
                    2
                );
                $this->setTripleAtRelative(
                    $p0 + $v0,
                    $p1 + $v1,
                    $p0 + $v0 - $cPoint,
                    $p1 + $v1,
                    $p0 + $v0,
                    $p1 + $v1,
                    3
                );
            } else {
                $this->setTripleAtRelative(
                    $p0 + $v0 - $round,
                    $p1 - $v1,
                    $p0 + $v0 - $round,
                    $p1 - $v1,
                    $p0 + $v0 - $cPoint,
                    $p1 - $v1,
                    1
                );
                $this->setTripleAtRelative(
                    $p0 - $v0 + $round,
                    $p1 - $v1,
                    $p0 - $v0 + $cPoint,
                    $p1 - $v1,
                    $p0 - $v0 + $round,
                    $p1 - $v1,
                    2
                );
                $this->setTripleAtRelative(
                    $p0 - $v0,
                    $p1 - $v1 + $round,
                    $p0 - $v0,
                    $p1 - $v1 + $round,
                    $p0 - $v0,
                    $p1 - $v1 + $cPoint,
                    3
                );
                $this->setTripleAtRelative(
                    $p0 - $v0,
                    $p1 + $v1 - $round,
                    $p0 - $v0,
                    $p1 + $v1 - $cPoint,
                    $p0 - $v0,
                    $p1 + $v1 - $round,
                    4
                );
                $this->setTripleAtRelative(
                    $p0 - $v0 + $round,
                    $p1 + $v1,
                    $p0 - $v0 + $round,
                    $p1 + $v1,
                    $p0 - $v0 + $cPoint,
                    $p1 + $v1,
                    5
                );
                $this->setTripleAtRelative(
                    $p0 + $v0 - $round,
                    $p1 + $v1,
                    $p0 + $v0 - $cPoint,
                    $p1 + $v1,
                    $p0 + $v0 - $round,
                    $p1 + $v1,
                    6
                );
                $this->setTripleAtRelative(
                    $p0 + $v0,
                    $p1 + $v1 - $round,
                    $p0 + $v0,
                    $p1 + $v1 - $round,
                    $p0 + $v0,
                    $p1 + $v1 - $cPoint,
                    7
                );
            }
        }
    }

    public function getValue(?bool $_flag = false)
    {
        if (($this->elem?->globalData['frameId'] ?? null) === $this->frameId) {
            return 0; // For type compability
        }
        if ($this->elem?->globalData['frameId'] ?? null) {
            $this->frameId = $this->elem->globalData['frameId'];
        }

        $this->iterateDynamicProperties();
        if ($this->_mdf) {
            $this->convertRectToPath();
        }

        return 0; // For type compability
    }
}
