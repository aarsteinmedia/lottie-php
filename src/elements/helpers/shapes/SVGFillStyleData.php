<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\PropertyFactory;

class SVGFillStyleData extends DynamicPropertyContainer
{
    public ?BaseProperty $c;
    public ?\DOMElement $gr;
    public $it = [];
    public ?BaseProperty $o;
    public $prevViewData = [];
    public SVGStyleData $style;
    public ?array $transform;
    public ?BaseProperty $w;

    public function __construct(
        BaseElement|DynamicPropertyContainer $elem,
        array $data,
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
