<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Pooling\ShapeCollectionPool;
use LottiePHP\Utils\Pooling\ShapePool;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\PropertyFactory;

class EllShapeProperty extends ShapeBaseProperty
{
    public float $_cPoint;
    public ?int $d;
    public BaseProperty $s;

    public function __construct(BaseElement|BaseProperty $elem, array $data)
    {
        $this->_cPoint = Constants::$roundCorner;
        $this->v = ShapePool::newElement();
        $this->v->setPathData(true, 4);
        $this->localShapeCollection = ShapeCollectionPool::newShapeCollection();
        $this->paths = $this->localShapeCollection;
        $this->localShapeCollection->addShape($this->v);
        $this->d = $data['d'];
        $this->elem = $elem;
        $this->comp = $elem->comp;
        $this->frameId = -1;
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
        if (count($this->dynamicProperties) > 0) {
            $this->k = true;
        } else {
            $this->k = false;
            $this->convertEllToPath();
        }
    }

    public function convertEllToPath()
    {
        if (!$this->p) {
            return;
        }

        $p0 = $this->p->v[0];
        $p1 = $this->p->v[1];
        $s0 = $this->s->v[0] / 2;
        $s1 = $this->s->v[1] / 2;
        $_cw = 3 !== $this->d;
        $_v = $this->v;

        if (!$_v?->v[0] || !$_v->v[1] || !$_v->v[2] || !$_v->v[3] || !$_v->i[0] || !$_v->i[1] || !$_v->i[2] || !$_v->i[3] || !$_v->o[0] || !$_v->o[1] || !$_v->o[2] || !$_v->o[3]) {
            throw new \Error(get_class($this).': Could not get value of ellipse');
        }
        $_v->v[0][0] = $p0;
        $_v->v[0][1] = $p1 - $s1;
        $_v->v[1][0] = $_cw ? $p0 + $s0 : $p0 - $s0;
        $_v->v[1][1] = $p1;
        $_v->v[2][0] = $p0;
        $_v->v[2][1] = $p1 + $s1;
        $_v->v[3][0] = $_cw ? $p0 - $s0 : $p0 + $s0;
        $_v->v[3][1] = $p1;
        $_v->i[0][0] = $_cw ? $p0 - $s0 * $this->_cPoint : $p0 + $s0 * $this->_cPoint;
        $_v->i[0][1] = $p1 - $s1;
        $_v->i[1][0] = $_cw ? $p0 + $s0 : $p0 - $s0;
        $_v->i[1][1] = $p1 - $s1 * $this->_cPoint;
        $_v->i[2][0] = $_cw ? $p0 + $s0 * $this->_cPoint : $p0 - $s0 * $this->_cPoint;
        $_v->i[2][1] = $p1 + $s1;
        $_v->i[3][0] = $_cw ? $p0 - $s0 : $p0 + $s0;
        $_v->i[3][1] = $p1 + $s1 * $this->_cPoint;
        $_v->o[0][0] = $_cw ? $p0 + $s0 * $this->_cPoint : $p0 - $s0 * $this->_cPoint;
        $_v->o[0][1] = $p1 - $s1;
        $_v->o[1][0] = $_cw ? $p0 + $s0 : $p0 - $s0;
        $_v->o[1][1] = $p1 + $s1 * $this->_cPoint;
        $_v->o[2][0] = $_cw ? $p0 - $s0 * $this->_cPoint : $p0 + $s0 * $this->_cPoint;
        $_v->o[2][1] = $p1 + $s1;
        $_v->o[3][0] = $_cw ? $p0 - $s0 : $p0 + $s0;
        $_v->o[3][1] = $p1 - $s1 * $this->_cPoint;

        // Normalize tangents to Lottie semantics: in/out tangents are stored as
        // relative vectors to the vertex, not absolute points.
        for ($i = 0; $i < 4; ++$i) {
            $_v->i[$i][0] -= $_v->v[$i][0];
            $_v->i[$i][1] -= $_v->v[$i][1];
            $_v->o[$i][0] -= $_v->v[$i][0];
            $_v->o[$i][1] -= $_v->v[$i][1];
        }
    }

    public function getValue(?bool $flag = null)
    {
        if ($this->elem?->globalData['frameId'] === $this->frameId) {
            return 0;
        }
        $this->frameId = $this->elem?->globalData['frameId'] ?? 0;
        $this->iterateDynamicProperties();

        if ($this->_mdf) {
            $this->convertEllToPath();
        }

        return 0;
    }
}
