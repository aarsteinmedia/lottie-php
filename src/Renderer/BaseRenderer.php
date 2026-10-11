<?php

namespace LottiePHP\Renderer;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Elements\Helpers\FrameElement;
use LottiePHP\Elements\ImageElement;
use LottiePHP\Elements\NullElement;
use LottiePHP\Elements\SolidElement;
use LottiePHP\Elements\SVGCompElement;
use LottiePHP\Elements\SVGShapeElement;
use LottiePHP\Elements\SVGTextLottieElement;

abstract class BaseRenderer extends FrameElement
{
    use RendererTraits;

    public function addPendingElement(BaseElement $element)
    {
        array_push($this->pendingElements, $element);
    }

    abstract public function buildItem(int $val);

    abstract public function checkPendingElements();

    abstract public function createComp(
        array $data,
        ?\DOMElement $container = null,
        ?array $comp = null,
        $_ = null
    ): SVGCompElement;

    abstract public function createImage(array $layer): ImageElement;

    abstract public function createNull(array $layer): NullElement;

    abstract public function createShape(array $layer): SVGShapeElement;

    abstract public function createSolid(array $layer): SolidElement;

    abstract public function createText(array $layer): SVGTextLottieElement;

    abstract public function prepareFrame(float $num);
}
