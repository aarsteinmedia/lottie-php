<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableDOMElement;

abstract class SVGBaseElement extends RenderableDOMElement
{
    use SVGBaseTraits;
    public ?array $matteMasks;
}
