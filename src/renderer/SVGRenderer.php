<?php

namespace LottiePHP\Renderer;

use LottiePHP\Animation\AnimationItem;
use LottiePHP\Elements\SVGCompElement;

use function LottiePHP\Utils\createNS;

class SVGRenderer extends SVGRendererBase
{
    public ?AnimationItem $animationItem;
    public ?\DOMElement $layerElement;
    public array $renderConfig;

    public function __construct(
        AnimationItem $animationItem,
        ?array $config = []
    ) {
        $this->animationItem = $animationItem;
        $this->svgElement = createNS('svg');

        $this->animationItem->wrapper->appendChild($this->svgElement);

        $defs = createNS('defs');
        $this->svgElement->appendChild($defs);

        $mask = createNS('g');
        $this->svgElement->appendChild($mask);
        $this->layerElement = $mask;

        // Lottie-web expects a globalData bag available everywhere.
        // For still rendering we keep this minimal but non-null.
        $this->globalData = [
            'defs' => $defs,
            'frameNum' => 0,
            'frameId' => 0,
            '_mdf' => true,
            'progressiveLoad' => false,
            'projectInterface' => [
                'currentFrame' => 0,
                'registerComposition' => static function (): void {},
            ],
        ];

        $this->renderConfig = [
            'contentVisibility' => $config['contentVisibility'] ?? 'visible',
            'filterSize' => [
                'height' => $config['filterSize']['height'] ?? '100%',
                'width' => $config['filterSize']['width'] ?? '100%',
                'x' => $config['filterSize']['x'] ?? '0%',
                'y' => $config['filterSize']['y'] ?? '0%',
            ],
            'focusable' => $config['focusable'] ?? null,
            'height' => $config['height'] ?? null,
            'hideOnTransparent' => false !== ($config['hideOnTransparent'] ?? true),
            'imagePreserveAspectRatio' => $config['imagePreserveAspectRatio'] ?? 'xMidYMid slice',
            'preserveAspectRatio' => $config['preserveAspectRatio'] ?? 'xMidYMid meet',
            'viewBoxSize' => $config['viewBoxSize'] ?? false,
            'width' => $config['width'] ?? null,
            'viewBoxOnly' => $config['viewBoxOnly'] ?? false,
            'className' => $config['className'] ?? null,
            'id' => $config['id'] ?? null,
            'progressiveLoad' => $config['progressiveLoad'] ?? false,
        ];
    }

    public function toSVGString(): string
    {
        $xml = $this->svgElement->ownerDocument?->saveXML($this->svgElement);

        return is_string($xml) ? $xml : '';
    }

    public function createComp(
        array $data,
        ?\DOMElement $_container = null,
        ?array $_comp = null,
        $_ = null
    ): SVGCompElement {
        if (!$this->globalData) {
            throw new \Error(get_class($this).': Can\'t access globalData');
        }

        return new SVGCompElement(
            $data,
            $this->globalData,
            $this
        );
    }
}
