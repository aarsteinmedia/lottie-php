<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Utils\Properties\KeyframeValueProperty;
use LottiePHP\Utils\Properties\TransformProperty;
use LottiePHP\Utils\Properties\ValueProperty;

class SVGTransformData
{
    public bool $_isAnimated;
    public $elements = [];
    public ?\DOMElement $gr;
    public ?array $it;
    public ?array $prevViewData;
    public ?SVGStyleData $style;
    public array $transform;

    public function __construct(
        TransformProperty $mProps,
        KeyframeValueProperty|ValueProperty $op,
        \DOMElement $container
    ) {
        $this->transform = [
            'container' => $container,
            'mProps' => $mProps,
            'op' => $op,
        ];
        $this->_isAnimated = count($this->transform['mProps']->dynamicProperties ?? []) > 0
          || count($this->transform['op']->effectsSequence ?? []) > 0;
    }
}
