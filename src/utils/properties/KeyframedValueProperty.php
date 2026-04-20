<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Elements\Helpers\Shapes\SVGShapeData;
use LottiePHP\Utils\Enums\PropType;

class KeyframedValueProperty extends BaseProperty
{
    public ?string $selectorValue;
    // BaseProperty defines `$v` already. Avoid redeclaring with a narrower type.

    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        ?float $mult = null,
        BaseElement|BaseProperty|null $container = null
    ) {
        $this->propType = PropType::UniDimensional;
        $this->keyframes = $data['k'];
        $this->keyframesMetadata = [];
        $this->offsetTime = $elem['data']['st'] ?? 0;
        $this->frameId = -1;
        $this->_caching = [
            '_lastKeyframeIndex' => -1,
            'lastFrame' => $this->initFrame,
            'lastIndex' => 0,
            'value' => 0,
        ];
        $this->k = true;
        $this->kf = true;
        $this->data = $data;
        $this->mult = $mult ?? 1.0;
        $this->elem = $elem;
        $this->container = $container;
        $this->comp = $elem['comp'];
        $this->v = $this->initFrame;
        $this->pv = $this->initFrame;
        $this->_isFirstFrame = true;
    }

    public function effectsSequence(...$args)
    {
        return $this->getValueAtCurrentTime(...$args);
    }

    public function getValue(...$args)
    {
        return $this->processEffectsSequence(...$args);
    }
}
