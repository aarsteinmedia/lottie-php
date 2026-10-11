<?php

namespace LottiePHP\Renderer;

use LottiePHP\Animation\AnimationItem;
use LottiePHP\Elements\SVGCompElement;
use LottiePHP\Utils\Helpers\GlobalData;

use function LottiePHP\Utils\createNS;
use function LottiePHP\Utils\createTag;

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

        $defs = createTag('defs');
        $this->svgElement->appendChild($defs);

        $mask = createTag('g');
        $this->svgElement->appendChild($mask);
        $this->layerElement = $mask;

        // Lottie-web expects a globalData bag available everywhere.
        // For still rendering we keep this minimal but non-null.
        $this->globalData = new GlobalData([
            'defs' => $defs,
            'frameNum' => 0,
            'frameId' => 0,
            '_mdf' => true,
            'stillFrame' => true,
            'projectInterface' => [
                'currentFrame' => 0,
            ],
        ]);

        $this->renderConfig = [
            'contentVisibility' => $config['contentVisibility'] ?? 'visible',
            // When true, emit `data-lottie-*` attributes on layer <g> nodes for debugging.
            // Defaults to false to keep SVG output clean.
            'debugAttributes' => (bool) ($config['debugAttributes'] ?? false),
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
        ];
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

        // Pre-comp layers (`ty: 0`) reference their actual layer stack via `refId`.
        // In Lottie JSON, the referenced composition lives under `assets[]`.
        // If we don't resolve it here, the comp element will be instantiated with
        // an empty `layers` array and render as an empty `<g>` (often masked).
        if (
            isset($data['refId'])
            && is_string($data['refId'])
            && isset($this->globalData['getAssetData'])
            && is_callable($this->globalData['getAssetData'])
        ) {
            /** @var ?array $asset */
            $asset = ($this->globalData['getAssetData'])($data['refId']);
            if (is_array($asset)) {
                if (!isset($data['layers']) && isset($asset['layers']) && is_array($asset['layers'])) {
                    $data['layers'] = $asset['layers'];
                }
                // Precomp dimensions are defined on the asset, not always on the layer.
                if (!isset($data['w']) && isset($asset['w'])) {
                    $data['w'] = $asset['w'];
                }
                if (!isset($data['h']) && isset($asset['h'])) {
                    $data['h'] = $asset['h'];
                }
            }
        }

        return new SVGCompElement(
            $data,
            $this->globalData,
            $this
        );
    }

    public function getBaseElement(): ?\DOMElement
    {
        return $this->svgElement;
    }

    /**
     * The root renderer has no frame of its own; `renderFrame()` prepares each layer.
     */
    public function prepareFrame(float $num)
    {
        throw new \LogicException(get_class($this).': Use renderFrame() on the root renderer');
    }

    public function toSVGString(): string
    {
        $xml = $this->svgElement->ownerDocument?->saveXML(
            $this->svgElement,
            LIBXML_NOXMLDECL
        );

        return is_string($xml) ? $xml : '';
    }
}
