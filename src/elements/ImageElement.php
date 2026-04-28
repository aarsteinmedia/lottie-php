<?php

namespace LottiePHP\Elements;

use LottiePHP\Utils\Helpers\Constants;

use function LottiePHP\Utils\createNS;

class ImageElement extends SVGBaseElement
{
    public array $assetData = [];
    public array $layers = [];
    public array $sourceRect = [];

    public function __construct(
        array $data,
        array $globalData,
        BaseElement $comp
    ) {
        parent::__construct();

        if ($data['refId'] ?? null) {
            $this->assetData = $globalData['getAssetData']($data['refId']) ?? null;
        }

        if ($this->assetData['sid'] ?? null) {
            $this->assetData = $globalData['slotManager']?->getProp($this->assetData) ?? [];
        }

        $this->initElement(
            $data,
            $globalData,
            $comp
        );

        $this->sourceRect = [
            'height' => $this->assetData['h'] ?? 0,
            'left' => 0,
            'top' => 0,
            'width' => $this->assetData['w'] ?? 0,
        ];
    }

    public function createContent()
    {
        if ($this->assetData && $this->globalData['getAssetsPath'] ?? null) {
            $assetPath = $this->globalData['getAssetsPath']($this->assetData);
        }

        if ($this->assetData) {
            $this->innerElem = createNS('image');
            $this->innerElem->setAttribute('width', "{$this->assetData['w']}px");
            $this->innerElem->setAttribute('height', "{$this->assetData['h']}px");
            $this->innerElem->setAttribute(
                'preserveAspectRatio',
                $this->assetData['pr']
                ?? $this->globalData['renderConfig']['imagePreserveAspectRatio']
                ?? 'xMidYMid meet'
            );
            $this->innerElem->setAttributeNS(
                Constants::$namespaceXlink,
                'xlink:href',
                $assetPath ?? ''
            );

            $this->layerElement?->appendChild($this->innerElem);
        }
    }

    public function renderInnerContent()
    {
        // Pass through
    }

    public function sourceRectAtTime()
    {
        return $this->sourceRect;
    }
}
