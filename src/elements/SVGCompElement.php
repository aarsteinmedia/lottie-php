<?php

namespace LottiePHP\Elements;

use LottiePHP\Renderer\RendererTraits;
use LottiePHP\Renderer\SVGRendererTraits;
use LottiePHP\Renderer\SVGRendererBase;
use LottiePHP\Utils\Properties\KeyframedValueProperty;
use LottiePHP\Utils\PropertyFactory;

use function LottiePHP\Utils\createSizedArray;

class SVGCompElement extends SVGBaseElement
{
    use SVGRendererTraits;
    use RendererTraits;

    public ?bool $_debug;
    public $completeLayers = false;
    // public function createAudio = SVGRendererBase.prototype.createAudio;
    // public function createCamera = SVGRendererBase.prototype.createCamera;
    public $currentFrame = 0;
    public function destroy = CompElement.prototype.destroy;
    public function destroyElements = CompElement.prototype.destroyElements;
    public array $elements;
    public function findIndexByInd = SVGRendererBase.prototype.findIndexByInd;
    public function getElementById = SVGRendererBase.prototype.getElementById;
    public function getElementByPath = SVGRendererBase.prototype.getElementByPath;
    public function getElements = CompElement.prototype.getElements;
    public function hide = CompElement.prototype.hide;
    public function includeLayers = SVGRendererBase.prototype.includeLayers;
    public function initElement = CompElement.prototype.initElement;
    public function initItems = SVGRendererBase.prototype.initItems;
    public ?array $layers;
    public $pendingElements = [];
    public function prepareFrame = CompElement.prototype.prepareFrame;
    public $renderedFrame = -1;
    public function renderFrame = CompElement.prototype.renderFrame;
    public function renderInnerContent = CompElement.prototype.renderInnerContent;
    public function searchExtraCompositions = SVGRendererBase.prototype.searchExtraCompositions;
    public function setElements = CompElement.prototype.setElements;
    public function setProjectInterface = SVGRendererBase.prototype.setProjectInterface;
    public function setupGlobalData = SVGRendererBase.prototype.setupGlobalData;
    public function show = CompElement.prototype.show;
    public $supports3d = true;
    public ?KeyframedValueProperty $tm;
    public function updateContainerSize = SVGRendererBase.prototype.updateContainerSize;

    public function __construct(
        array $data,
        array $globalData,
        SVGCompElement|SVGTextLottieElement $comp
    ) {
        $this->layers = $data['layers'];
        $this->elements = createSizedArray(count($this->layers ?? []));

        $this->initElement(
            $data,
            $globalData,
            $comp
        );
        $this->tm = ($data['tm'] ? PropertyFactory::getProp(
            $this,
            $data['tm'],
            0,
            $globalData['frameRate'],
        ) : [ '_placeholder' => true ]);
    }

    public function createComp(array $data)
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this) . ": Cannot access global data");
        }

        return new SVGCompElement(
            $data,
            $this->globalData,
            $this
        );
    }
}
