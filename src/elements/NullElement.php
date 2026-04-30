<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\FrameElement;

class NullElement extends FrameElement
{
    public function __construct(
        array $data,
        array $globalData,
        BaseElement $comp
    ) {
        $this->initFrame();
        $this->initBaseData(
            $data,
            $globalData,
            $comp
        );
        $this->initTransform();
        $this->initHierarchy();
    }

    public function getBaseElement(): ?\DOMElement
    {
        return null;
    }

    public function getMatte(?int $id)
    {
        // Null layers don't render DOM output; track mattes have no effect.
        return null;
    }

    public function prepareFrame(float $num)
    {
        $this->prepareProperties($num, true);
    }

    public function renderFrame(?int $_frame = null)
    {
        return null;
    }

    public function setMatte(?string $id)
    {
        // Null layers don't render DOM output; track mattes have no effect.
        return null;
    }
}
