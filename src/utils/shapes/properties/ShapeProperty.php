<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Pooling\ShapeCollectionPool;
use LottiePHP\Utils\Pooling\ShapePool;

class ShapeProperty extends ShapeBaseProperty
{
    public ?int $ix;
    public ?array $pathsData;
    public ?array $shape;
    public ?int $totalShapeLength;
    public ?bool $x;

    public function __construct(
        BaseElement|DynamicPropertyContainer $elem,
        array $data,
        int $type
    ) {
        $this->propType = PropType::Shape;
        $this->comp = $elem->comp;
        $this->container = $elem;
        $this->elem = $elem;
        $this->data = $data;
        $this->k = false;
        $this->kf = false;
        $this->_mdf = false;
        $pathData = 3 === $type ? ($data['pt']['k'] ?? null) : $data['ks']['k'];

        if (!$pathData) {
            throw new \Error(get_class($this).': Could now get Path Data');
        }

        // Lottie JSON uses plain arrays for path data.
        // Normalize to a `ShapePath` instance so downstream code can rely on methods/fields.
        if (is_array($pathData) && isset($pathData['i'], $pathData['o'], $pathData['v'])) {
            $len = count($pathData['v'] ?? []);
            $shapePath = ShapePool::newElement();
            $shapePath->setPathData((bool) ($pathData['c'] ?? false), $len);
            for ($i = 0; $i < $len; ++$i) {
                $v = $pathData['v'][$i] ?? [0, 0];
                $o = $pathData['o'][$i] ?? [0, 0];
                $in = $pathData['i'][$i] ?? [0, 0];
                $shapePath->setTripleAt(
                    (float) ($v[0] ?? 0),
                    (float) ($v[1] ?? 0),
                    (float) ($o[0] ?? 0),
                    (float) ($o[1] ?? 0),
                    (float) ($in[0] ?? 0),
                    (float) ($in[1] ?? 0),
                    $i,
                    true
                );
            }
            $this->v = $shapePath;
            $this->pv = ShapePool::clone($shapePath);
        } else {
            $this->v = $pathData;
            $this->pv = $this->v;
        }
        $this->localShapeCollection = ShapeCollectionPool::newShapeCollection();
        $this->paths = $this->localShapeCollection;
        $this->paths->addShape($this->v);
        $this->effectsSequence = [];
    }

    public function getValue(...$args)
    {
        return $this->processEffectsSequence(...$args);
    }
}
