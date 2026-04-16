<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Enums\PropType;

use function LottiePHP\Utils\createTypedArray;

class MultiDimensionalProperty extends BaseProperty {
  public $v;
  public function __construct(
    BaseProperty|BaseElement $elem,
    array $data,
    ?int $mult = null,
    BaseProperty|BaseElement $container
  ) {
    $this->propType = PropType::MultiDimensional;
    $this->mult = $mult || 1;
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
    for ($i = 0; $i < $length; $i++) {
      $this->v[$i] = ($data['k'][$i] ?? 0) * $this->mult;
      $this->pv[$i] = $data['k'][$i] ?? 0;
    }
    $this->_isFirstFrame = true;
    $this->effectsSequence = [];
  }

  public function getValue(...$args)
  {
    return $this->processEffectsSequence(...$args);
  }
}