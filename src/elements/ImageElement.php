<?php

namespace LottiePHP\Elements;

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
        
        if ($data['refId']) {
            $this->assetData = $globalData['getAssetData']($data['refId']);
        }

        if ($this->assetData['sid']) {
            $this->assetData = $globalData['slotManager']['getProp']($this->assetData) ?? [];
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
          'width' => $this->assetData['w'] ?? 0
        ];
    }
}
