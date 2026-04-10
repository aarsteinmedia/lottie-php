<?php

namespace LottiePHP\Elements;

use DOMElement;
use LottiePHP\Elements\Helpers\RenderableDOMElement;

abstract class SVGBaseElement extends RenderableDOMElement
{
    public DOMElement $maskedElement;
    public DOMElement $matteElement;
    public array $matteMasks = [];
    public DOMElement $transformedElement;

    public function createContainerElements()
    {
        // $this->maskedElement
    }
}
