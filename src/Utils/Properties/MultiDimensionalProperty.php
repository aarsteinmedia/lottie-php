<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\General;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

class MultiDimensionalProperty extends BaseProperty
{
    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        ?float $factor = null,
        BaseElement|BaseProperty|DynamicPropertyContainer|null $container = null
    ) {
        $this->propType = PropType::MultiDimensional;
        // See `ValueProperty`: treat 0 as “default multiplier”.
        $this->factor = $factor ?: 1.0;
        $this->data = $data;
        $this->_mdf = false;
        $this->elem = $elem;
        $this->container = $container;
        $this->comp = $elem['comp'];
        $this->k = false;
        $this->kf = false;
        $this->frameId = -1;

        $length = count($data['k']);

        $this->v = General::createTypedArray(ArrayType::Float32, $length);
        $this->pv = General::createTypedArray(ArrayType::Float32, $length);
        $this->vel = General::createTypedArray(ArrayType::Float32, $length);
        for ($i = 0; $i < $length; ++$i) {
            $this->v[$i] = ($data['k'][$i] ?? 0) * $this->factor;
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
