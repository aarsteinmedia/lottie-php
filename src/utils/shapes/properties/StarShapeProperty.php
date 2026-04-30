<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Pooling\ShapeCollectionPool;
use LottiePHP\Utils\Pooling\ShapePool;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\ShapeCollection;

class StarShapeProperty extends ShapeBaseProperty
{
    public mixed $d;
    public ?BaseProperty $ir;
    public ?BaseProperty $is;
    public BaseProperty $or;
    public BaseProperty $os;
    public BaseProperty $pt;
    public BaseProperty $r;
    public ?BaseProperty $s;

    public function __construct(BaseElement|BaseProperty $elem, array $data)
    {
        $this->v = ShapePool::newElement();
        $this->v->setPathData(true, 0);
        $this->elem = $elem;
        $this->comp = $elem->comp;
        $this->data = $data;
        $this->frameId = -1;
        $this->d = $data['d'];
        $this->initDynamicPropertyContainer($elem);

        if (1 === $data['sy']) {
            $this->ir = PropertyFactory::getProp(
                $elem,
                $data['ir'],
                0,
                0,
                $this
            );
            $this->is = PropertyFactory::getProp(
                $elem,
                $data['is'],
                0,
                0.01,
                $this
            );
        }

        $this->pt = PropertyFactory::getProp(
            $elem,
            $data['pt'],
            0,
            0,
            $this
        );
        $this->p = PropertyFactory::getProp(
            $elem,
            $data['p'],
            1,
            0,
            $this
        );
        $this->r = PropertyFactory::getProp(
            $elem,
            $data['r'],
            0,
            Constants::$degToRads,
            $this
        );
        $this->or = PropertyFactory::getProp(
            $elem,
            $data['or'],
            0,
            0,
            $this
        );
        $this->os = PropertyFactory::getProp(
            $elem,
            $data['os'],
            0,
            0.01,
            $this
        );
        $this->localShapeCollection = ShapeCollectionPool::newShapeCollection();
        $this->localShapeCollection->addShape($this->v);
        $this->paths = $this->localShapeCollection;
        if (count($this->dynamicProperties) > 0) {
            $this->k = true;
        } else {
            $this->k = false;
            $this->convertToPath();
        }
    }

    public function convertPolygonToPath()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (Shape) is not implemented');
        }

        if (!$this->p) {
            throw new \Error(get_class($this).': p value is not implemented');
        }

        $numPts = floor($this->pt->v);
        $angle = M_PI * 2 / $numPts;
        $rad = $this->or->v;
        $roundness = $this->os->v;
        $perimSegment = 2 * M_PI * $rad / ($numPts * 4);
        $currentAng = -M_PI * 0.5;
        $dir = 3 === $this->data['d'] ? -1 : 1;

        $currentAng += $this->r->v;
        if ($this->v) {
            $this->v->_length = 0;
        }

        for ($i = 0; $i < $numPts; ++$i) {
            $x = $rad * cos($currentAng);
            $y = $rad * sin($currentAng);
            $ox = 0 === $x && 0 === $y ? 0 : $y / sqrt($x * $x + $y * $y);
            $oy = 0 === $x && 0 === $y ? 0 : -$x / sqrt($x * $x + $y * $y);

            $x += $this->p->v[0];
            $y += $this->p->v[1];
            // ShapePath stores in/out tangents as relative vectors (Lottie JSON semantics),
            // not absolute positions. buildShapeString() will add them to vertices.
            $tX = $ox * $perimSegment * $roundness * $dir;
            $tY = $oy * $perimSegment * $roundness * $dir;
            $this->v?->setTripleAt(
                $x,
                $y,
                -$tX,
                -$tY,
                $tX,
                $tY,
                $i,
                true
            );
            $currentAng += $angle * $dir;
        }
    }

    public function convertStarToPath()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (Shape) is not implemented');
        }

        if (!$this->v) {
            throw new \Error(get_class($this).': v (ShapePath) is not implemented');
        }

        $numPts = floor($this->pt->v) * 2;
        $angle = M_PI * 2 / $numPts;
        /* $this->v->v.length = numPts;
                  $this->v->i.length = numPts;
                  $this->v->o.length = numPts; */
        $isLong = true;
        $longRad = $this->or->v;
        $shortRad = (float) $this->ir?->v;
        $longRound = $this->os->v;
        $shortRound = (float) $this->is?->v;
        $longPerimSegment = 2 * M_PI * $longRad / ($numPts * 2);
        $shortPerimSegment = 2 * M_PI * $shortRad / ($numPts * 2);
        $rad = 0;
        $roundness = 0;
        $perimSegment = 0;
        $currentAng = -M_PI / 2;

        $currentAng += $this->r->v;
        $dir = 3 === $this->data['d'] ? -1 : 1;

        $this->v->_length = 0;
        for ($i = 0; $i < $numPts; ++$i) {
            $rad = $isLong ? $longRad : $shortRad;
            $roundness = $isLong ? $longRound : $shortRound;
            $perimSegment = $isLong ? $longPerimSegment : $shortPerimSegment;
            $x = $rad * cos($currentAng);
            $y = $rad * sin($currentAng);
            $ox = 0 === $x && 0 === $y ? 0 : $y / sqrt($x * $x + $y * $y);
            $oy = 0 === $x && 0 === $y ? 0 : -$x / sqrt($x * $x + $y * $y);

            $x += (float) $this->p?->v[0];
            $y += (float) $this->p?->v[1];
            // Store tangents as relative vectors (Lottie semantics).
            $tX = $ox * $perimSegment * $roundness * $dir;
            $tY = $oy * $perimSegment * $roundness * $dir;
            $this->v->setTripleAt(
                $x,
                $y,
                -$tX,
                -$tY,
                $tX,
                $tY,
                $i,
                true
            );

            /* $this->v->v[$i] = [x,y];
                        $this->v->i[$i] = [x+ox*$perimSegment*$roundness*dir,y+oy*$perimSegment*$roundness*dir];
                        $this->v->o[$i] = [x-ox*$perimSegment*$roundness*dir,y-oy*$perimSegment*$roundness*dir];
                        $this->v->_length = numPts; */
            $isLong = !$isLong;
            $currentAng += $angle * $dir;
        }
    }

    public function convertToPath()
    {
        if (1 === $this->data['sy']) {
            return $this->convertStarToPath();
        }

        return $this->convertPolygonToPath();
    }

    public function getValue(?bool $_val)
    {
        if (($this->elem?->globalData['frameId'] ?? null) === $this->frameId) {
            return 0;
        }
        $this->frameId = $this->elem?->globalData['frameId'] ?? 0;
        $this->iterateDynamicProperties();
        if ($this->_mdf) {
            $this->convertToPath();
        }

        return 0;
    }
}
