<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;

use function LottiePHP\Utils\createNS;

class SVGTintFilter extends SVGComposableEffect
{
    public GroupEffect $filterManager;
    public \DOMElement $linearFilter;
    public \DOMElement $matrixFilter;

    public function __construct(
        \DOMElement $filter,
        GroupEffect $filterManager,
        BaseElement|BaseProperty $_elem,
        string $id,
        string $source
    ) {
        $this->filterManager = $filterManager;
        $feColorMatrix = createNS('feColorMatrix');

        $feColorMatrix->setAttribute('type', 'matrix');
        $feColorMatrix->setAttribute('color-interpolation-filters', 'linearRGB');
        $feColorMatrix->setAttribute('values', "{$this->linearFilterValue} 1 0");
        $this->linearFilter = $feColorMatrix;
        $feColorMatrix->setAttribute('result', "{$id}_tint_1");
        $filter->appendChild($feColorMatrix);
        $feColorMatrix = createNS('feColorMatrix');
        $feColorMatrix->setAttribute('type', 'matrix');
        $feColorMatrix->setAttribute('color-interpolation-filters', 'sRGB');
        $feColorMatrix->setAttribute(
            'values',
            '1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 1 0'
        );
        $feColorMatrix->setAttribute('result', "{$id}_tint_2");
        $filter->appendChild($feColorMatrix);
        $this->matrixFilter = $feColorMatrix;

        $feMerge = $this->createMergeNode($id, [
            $source,
            "{$id}_tint_1",
            "{$id}_tint_2",
        ]);

        $filter->appendChild($feMerge);
    }

    public function renderFrame(?bool $forceRender)
    {
        if (
            !$forceRender && !$this->filterManager->_mdf
            // !$this->filterManager->effectElements
        ) {
            return;
        }
        $colorBlack = $this->filterManager->effectElements[0]['p']['v'] ?? [0, 0, 0];
        $colorWhite = $this->filterManager->effectElements[1]['p']['v'] ?? [255, 255, 255];
        $opacity = ($this->filterManager->effectElements[2]['p']['v'] ?? 100) / 100;

        $this->linearFilter->setAttribute(
            'values',
            "{$this->linearFilterValue} {$opacity} 0"
        );
        $color0 = $colorWhite[0] - $colorBlack[0];
        $color1 = $colorWhite[1] - $colorBlack[1];
        $color2 = $colorWhite[2] - $colorBlack[2];
        $this->matrixFilter->setAttribute(
            'values',
            "{$color0} 0 0 0 {$colorBlack[0]} {$color1} 0 0 0 {$colorBlack[1]} {$color2} 0 0 0 {$colorBlack[2]} 0 0 0 1 0"
        );
    }

    private $linearFilterValue
        = '0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0 0 0';
}
