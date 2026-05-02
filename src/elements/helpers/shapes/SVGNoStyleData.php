<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

class SVGNoStyleData extends DynamicPropertyContainer
{
    public ?\DOMElement $gr;
    public $it = [];
    public $prevViewData = [];
    public SVGStyleData $style;
    public ?array $transform;

    public function __construct(
        BaseElement|DynamicPropertyContainer $elem,
        array $_data,
        SVGStyleData $styleObj
    ) {
        $this->initDynamicPropertyContainer($elem);
        $this->style = $styleObj;
    }

    public function getValue(?bool $flag = false)
    {
        return $this->iterateDynamicProperties($flag);
    }
}
