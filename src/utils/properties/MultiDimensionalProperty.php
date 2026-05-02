<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

use function LottiePHP\Utils\createTypedArray;

class MultiDimensionalProperty extends BaseProperty
{
    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        ?float $mult = null,
        BaseElement|BaseProperty|DynamicPropertyContainer|null $container = null
    ) {
        $this->propType = PropType::MultiDimensional;
        // See `ValueProperty`: treat 0 as “default multiplier”.
        $this->mult = $mult ?: 1.0;
        $this->data = $data;
        $this->_mdf = false;
        $this->elem = $elem;
        $this->container = $container;
        $this->comp = $elem['comp'];
        $this->k = false;
        $this->kf = false;
        $this->frameId = -1;

        $length = count($data['k']);

        $this->v = createTypedArray(ArrayType::Float32, $length);
        $this->pv = createTypedArray(ArrayType::Float32, $length);
        $this->vel = createTypedArray(ArrayType::Float32, $length);
        for ($i = 0; $i < $length; ++$i) {
            $this->v[$i] = ($data['k'][$i] ?? 0) * $this->mult;
            $this->pv[$i] = $data['k'][$i] ?? 0;
        }
        $this->_isFirstFrame = true;
        $this->effectsSequence = [];
    }

    public function getValue(?bool $flag = null)
    {
        return $this->processEffectsSequence($flag);
    }
}
