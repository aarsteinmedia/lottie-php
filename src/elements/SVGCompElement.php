<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableElementTraits;
use LottiePHP\Renderer\BaseRenderer;
use LottiePHP\Renderer\RendererTraits;
use LottiePHP\Renderer\SVGRendererTraits;
use LottiePHP\Utils\Properties\KeyframedValueProperty;
use LottiePHP\Utils\PropertyFactory;

use function LottiePHP\Utils\createSizedArray;

class SVGCompElement extends SVGBaseElement
{
    use SVGRendererTraits;
    use RendererTraits;
    use CompTrait;
    use RenderableElementTraits;

    public ?bool $_debug;
    public $completeLayers = false;
    // public function createAudio = SVGRendererBase.prototype.createAudio;
    // public function createCamera = SVGRendererBase.prototype.createCamera;
    public $currentFrame = 0;
    public array $elements;
    public ?array $layers;
    public $pendingElements = [];
    public $renderedFrame = -1;
    // public function setProjectInterface = SVGRendererBase.prototype.setProjectInterface;
    public $supports3d = true;
    public ?KeyframedValueProperty $tm;

    public function __construct(
        array $data,
        array $globalData,
        BaseRenderer|SVGCompElement|SVGTextLottieElement $comp
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
        ) : ['_placeholder' => true]);
    }

    public function createComp(array $data): SVGCompElement
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).': Cannot access global data');
        }

        return new SVGCompElement(
            $data,
            $this->globalData,
            $this
        );
    }
}
