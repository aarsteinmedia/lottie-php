<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableDOMElement;

abstract class SVGBaseElement extends RenderableDOMElement
{
    use SVGBaseTraits;

    public ?bool $_sizeChanged;
    public ?\DOMElement $maskedElement;
    public ?\DOMElement $matteElement;
    public ?array $matteMasks;
    public ?\DOMElement $transformedElement;
}
