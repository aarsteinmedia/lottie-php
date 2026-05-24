<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;

use function LottiePHP\Utils\createTag;

class SVGFillFilter
{
    public GroupEffect $filterManager;
    public \DOMElement $matrixFilter;

    public function __construct(
        \DOMElement $filter,
        GroupEffect $filterManager,
        BaseElement|BaseProperty $_elem,
        string $id
    ) {
        $this->filterManager = $filterManager;
        $feColorMatrix = createTag('feColorMatrix');

        $feColorMatrix->setAttribute('type', 'matrix');
        $feColorMatrix->setAttribute('color-interpolation-filters', 'sRGB');
        $feColorMatrix->setAttribute(
            'values',
            '1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 1 0'
        );
        $feColorMatrix->setAttribute('result', $id);
        $filter->appendChild($feColorMatrix);
        $this->matrixFilter = $feColorMatrix;
    }

    public function renderFrame(?bool $forceRender = null)
    {
        if (
            !$forceRender && !$this->filterManager->_mdf
        ) {
            return;
        }
        $color = EffectProp::value($this->filterManager, 2, [0, 0, 0]);
        $opacity = EffectProp::value($this->filterManager, 6, 1);

        $this->matrixFilter->setAttribute(
            'values',
            "0 0 0 0 {$color[0]} 0 0 0 0 {$color[1]} 0 0 0 0 {$color[2]} 0 0 0 {$opacity} 0"
        );
    }
}
