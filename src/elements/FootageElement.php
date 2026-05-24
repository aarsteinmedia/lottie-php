<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableElement;

class FootageElement extends RenderableElement
{
    public ?array $assetData = null;
    public ?\DOMElement $footageData;

    public function __construct(
        array $data,
        array $globalData,
        BaseElement $comp
    ) {
        parent::__construct();

        $this->initFrame();
        $this->initRenderable();
        if ($data['refId']) {
            $this->assetData = $globalData['getAssetData']($data['refId']) ?? null;
        }
        $imageLoader = $globalData['imageLoader'] ?? null;
        $this->footageData = is_object($imageLoader) && method_exists($imageLoader, 'getAsset')
            ? $imageLoader->getAsset($this->assetData)
            : null;
        $this->initBaseData(
            $data,
            $globalData,
            $comp
        );
    }

    public function getBaseElement(): ?\DOMElement
    {
        return null;
    }

    public function getFootageData()
    {
        return $this->footageData;
    }

    public function initExpressions()
    {
        // $expressionsInterfaces = getExpressionInterfaces();

        // if (!$expressionsInterfaces) {
        //   return;
        // }

        // $footageInterface = ($expressionsInterfaces)('footage');

        // $this->layerInterface = new ($footageInterface)($this);
    }

    public function prepareFrame()
    {
        // Pass Through
    }

    public function renderFrame()
    {
        // Pass Through
    }
}
