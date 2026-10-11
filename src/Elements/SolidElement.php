<?php

namespace LottiePHP\Elements;

use LottiePHP\Utils\Helpers\GlobalData;

use function LottiePHP\Utils\createTag;

class SolidElement extends ImageElement
{
    public function __construct(
        array $data,
        GlobalData $globalData,
        BaseElement $comp,
    ) {
        parent::__construct(
            $data,
            $globalData,
            $comp
        );
        $this->initElement(
            $data,
            $globalData,
            $comp
        );
    }

    public function createContent()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        $rect = createTag('rect');

        $rect->setAttribute('width', $this->data['sw'] ?? 0);
        $rect->setAttribute('height', $this->data['sh'] ?? 0);

        if ($this->data['sc']) {
            $rect->setAttribute('fill', $this->data['sc']);
        }
        $this->layerElement?->appendChild($rect);
    }
}
