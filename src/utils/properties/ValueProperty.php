<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Text\TextAnimatorProperty;

class ValueProperty extends BaseProperty
{
    public $pv;
    public $v;

    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        ?int $mult = null,
        ?TextAnimatorProperty $container = null
    ) {
        $this->propType = PropType::UniDimensional;
        $this->mult = $mult || 1;
        $this->data = $data;
        $this->v = ($data['k'] * ($mult || 1));
        $this->pv = $data['k'];
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
