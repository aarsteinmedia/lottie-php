<?php

namespace LottiePHP\Utils\Shapes\Modifiers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Elements\Helpers\Shapes\SVGShapeData;
use LottiePHP\Elements\SVGShapeElement;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Pooling\ShapeCollectionPool;

class ShapeModifier extends DynamicPropertyContainer
{
    public ?bool $closed;
    public DynamicPropertyContainer|SVGShapeElement $elem;
    public ?int $frameId;
    public ?bool $k;
    public $shapes = [];

    public function addShape(SVGShapeData $data)
    {
        if ($this->closed) {
            return;
        }
        // Adding shape to dynamic properties. It covers the case where a shape has no effects applied, to reset it's _mdf state on every tick.
        $data->sh?->container?->addDynamicProperty($data->sh);

        $shapeData = (object) [
            'data' => $data,
            'localShapeCollection' => ShapeCollectionPool::newShapeCollection(),
            'shape' => $data->sh,
        ];

        $this->shapes[] = $shapeData;
        $this->addShapeToModifier($shapeData);
        if ($this->_isAnimated) {
            $data->setAsAnimated();
        }
    }

    public function addShapeToModifier(array|object $_shapeData)
    {
        // Pass through
    }

    public function init(
        BaseElement|DynamicPropertyContainer $elem,
        array $data,
        ?int $_posFromProps = null,
        ?array $_elemsData = null
    ) {
        $this->shapes = [];
        $this->elem = $elem;
        $this->initDynamicPropertyContainer($elem);
        $this->initModifierProperties($elem, $data);
        $this->frameId = Constants::$initialDefaultFrame;
        $this->closed = false;
        $this->k = false;
        if (count($this->dynamicProperties) > 0) {
            $this->k = true;
        } else {
            $this->getValue(true);
        }
    }

    public function initModifierProperties(
        BaseElement|DynamicPropertyContainer $_elem,
        array $_data
    ) {
        throw new \Error(get_class($this).': Method initModifierProperties is not implemented');
    }

    public function isAnimatedWithShape(array $_data)
    {
        throw new \Error(get_class($this).': Method isAnimatedWithShape is not implemented');
    }

    public function processKeys()
    {
        if ($this->elem?->globalData['frameId'] ?? null === $this->frameId) {
            return 0;
        }
        $this->frameId = $this->elem?->globalData['frameId'] ?? null;
        $this->iterateDynamicProperties();

        return 0;
    }
}
