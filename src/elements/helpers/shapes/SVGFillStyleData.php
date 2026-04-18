<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Properties\MultiDimensionalProperty;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;

class SVGFillStyleData extends DynamicPropertyContainer
{
    public ?MultiDimensionalProperty $c;
    public ?\DOMElement $gr;
    public $it = [];
    public ?ValueProperty $o;
    public $prevViewData = [];
    public SVGStyleData $style;
    public ?array $transform;
    public ?ValueProperty $w;

    public function __construct(
        BaseElement|DynamicPropertyContainer $elem,
        SVGShapeData $data,
        SVGStyleData $styleObj
    ) {
        $this->initDynamicPropertyContainer($elem);
        $this->o = PropertyFactory::getProp(
            $elem,
            $data['o'],
            0,
            0.01,
            $this
        );
        $this->c = PropertyFactory::getProp(
            $elem,
            $data['c'],
            1,
            255,
            $this
        );
        $this->style = $styleObj;
    }

    public function getValue(...$args)
    {
        return $this->iterateDynamicProperties(...$args);
    }
}
