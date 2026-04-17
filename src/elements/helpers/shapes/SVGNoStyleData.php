<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use DOMElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

class SVGNoStyleData extends DynamicPropertyContainer
{
    public ?DOMElement $gr;
    public $it = [];
    public $prevViewData = [];
    public SVGStyleData $style;
    public ?array $transform;
    public function __construct(
        DynamicPropertyContainer $elem,
        SVGShapeData $_data,
        SVGStyleData $styleObj
    ) {
        $this->initDynamicPropertyContainer($elem);
        $this->style = $styleObj;
    }

    public function getValue(...$args)
    {
        return $this->iterateDynamicProperties(...$args);
    }
}
