<?php

namespace LottiePHP\Renderer;

use DOMDocument;
use DOMElement;
use LottiePHP\Animation\AnimationItem;

class SVGRenderer extends SVGRendererBase
{
    private DOMDocument $_dom;

    public AnimationItem $animationItem;
    public DOMElement|false $layerElement;
    public array $renderConfig;

    public function __construct(AnimationItem $animationItem, array $config = [])
    {
        $this->animationItem = $animationItem;

        $this->_dom = new DOMDocument('1.0', 'UTF-8');
        $this->svgElement = $this->_dom->createElementNS('http://www.w3.org/2000/svg', 'svg');

        $defs = $this->_dom->createElementNS('http://www.w3.org/2000/svg', 'defs');
        $this->svgElement->appendChild($defs);

        $mask = $this->_dom->createElementNS('http://www.w3.org/2000/svg', 'g');
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

    public function createComp(array $data) {
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
