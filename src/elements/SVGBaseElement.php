<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableDOMElement;
use LottiePHP\Utils\FeatureSupport;
use LottiePHP\Utils\FiltersFactory;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\LocationHref;

use function LottiePHP\Utils\addClasses;
use function LottiePHP\Utils\createElementID;
use function LottiePHP\Utils\createNS;

abstract class SVGBaseElement extends RenderableDOMElement
{
    use SVGBaseTraits;

    public ?bool $_sizeChanged;
    public ?\DOMElement $maskedElement;
    public ?\DOMElement $matteElement;
    public ?array $matteMasks;
    public ?\DOMElement $transformedElement;
}
