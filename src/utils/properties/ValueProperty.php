<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

class ValueProperty extends BaseProperty
{
    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        ?float $mult = null,
        BaseElement|BaseProperty|DynamicPropertyContainer|null $container = null
    ) {
        $this->propType = PropType::UniDimensional;
        // In the upstream JS implementation, many call sites pass `0` for `mult`,
        // relying on `mult || 1` semantics. In PHP, `0 ?? 1` stays `0`, which
        // would zero out all values. Treat `0` as “no multiplier provided”.
        $this->mult = $mult ?: 1.0;
        $this->data = $data;
        $k = $data['k'] ?? 0;
        $this->v = ($k * ($this->mult ?? 1));
        $this->pv = $k;
        $this->_mdf = false;
        $this->elem = $elem;
        $this->container = $container;
        $this->comp = $elem['comp'];
        $this->k = false;
        $this->kf = false;
        $this->vel = 0;
        $this->effectsSequence = [];
        $this->_isFirstFrame = true;
    }

    public function getValue(...$args)
    {
        return $this->processEffectsSequence(...$args);
    }
}
