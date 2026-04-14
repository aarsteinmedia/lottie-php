<?php

namespace LottiePHP\Renderer;

use DOMDocument;
use DOMElement;
use LottiePHP\Animation\AnimationItem;

use function LottiePHP\Utils\createNS;

class SVGRenderer extends SVGRendererBase
{
    private DOMDocument $_dom;

    public AnimationItem $animationItem;
    public DOMElement|null $layerElement;
    public array $renderConfig;

    public function __construct(AnimationItem $animationItem, array $config = [])
    {
        $this->animationItem = $animationItem;

        $this->_dom = new DOMDocument('1.0', 'UTF-8');
        $this->svgElement = createNS('svg', $this->_dom);

        $defs = createNS('defs', $this->_dom);
        $this->svgElement->appendChild($defs);

        $mask = createNS('g', $this->_dom);
        $this->svgElement->appendChild($mask);
        $this->layerElement = $mask;

        $this->renderConfig[] = [
          'contentVisibility' => $config['contentVisibility'] || 'visible',
          'filterSize' => [
            'height' => $config['filterSize']['height'] ?? '100%',
            'width' => $config['filterSize']['width'] ?? '100%',
            'x' => $config['filterSize']['x'] ?? '0%',
            'y' => $config['filterSize']['y'] ?? '0%',
          ],
          'focusable' => $config['focusable'],
          'height' => $config['height'],
          'hideOnTransparent' => $config['hideOnTransparent'] !== false,
          'imagePreserveAspectRatio' =>
            $config['imagePreserveAspectRatio'] || 'xMidYMid slice',
          'preserveAspectRatio' => $config['preserveAspectRatio'] || 'xMidYMid meet',
          'viewBoxSize' => $config['viewBoxSize'] || false,
          'width' => $config['width'],
        ];
    }

    public function createComp(array $data): SVGCompElement
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this) . ': Can\'t access globalData');
        }

        return new SVGCompElement(
            $data,
            $this->globalData,
            $this
        );
    }
}
