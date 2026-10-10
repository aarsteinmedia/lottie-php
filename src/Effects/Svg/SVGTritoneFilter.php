<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;

use function LottiePHP\Utils\createTag;

class SVGTritoneFilter
{
    public \DOMElement $feFuncB;
    public \DOMElement $feFuncG;
    public \DOMElement $feFuncR;
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
        $feColorMatrix->setAttribute('color-interpolation-filters', 'linearRGB');
        $feColorMatrix->setAttribute(
            'values',
            '0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0 0 0 1 0'
        );
        $filter->appendChild($feColorMatrix);
        $feComponentTransfer = createTag('feComponentTransfer');

        $feComponentTransfer->setAttribute('color-interpolation-filters', 'sRGB');
        $feComponentTransfer->setAttribute('result', $id);
        $this->matrixFilter = $feComponentTransfer;
        $feFuncR = createTag('feFuncR');

        $feFuncR->setAttribute('type', 'table');
        $feComponentTransfer->appendChild($feFuncR);
        $this->feFuncR = $feFuncR;
        $feFuncG = createTag('feFuncG');

        $feFuncG->setAttribute('type', 'table');
        $feComponentTransfer->appendChild($feFuncG);
        $this->feFuncG = $feFuncG;
        $feFuncB = createTag('feFuncB');

        $feFuncB->setAttribute('type', 'table');
        $feComponentTransfer->appendChild($feFuncB);
        $this->feFuncB = $feFuncB;
        $filter->appendChild($feComponentTransfer);
    }

    public function renderFrame(?bool $forceRender = null)
    {
        if (
            !$forceRender && !$this->filterManager->_mdf // ||
            // !$this->filterManager->effectElements
        ) {
            return;
        }
        $color1 = EffectProp::value($this->filterManager, 0, [0, 0, 0]);
        $color2 = EffectProp::value($this->filterManager, 1, [0, 0, 0]);
        $color3 = EffectProp::value($this->filterManager, 2, [0, 0, 0]);
        $tableR = "{$color3[0]} {$color2[0]} {$color1[0]}";
        $tableG = "{$color3[1]} {$color2[1]} {$color1[1]}";
        $tableB = "{$color3[2]} {$color2[2]} {$color1[2]}";

        $this->feFuncR->setAttribute('tableValues', $tableR);
        $this->feFuncG->setAttribute('tableValues', $tableG);
        $this->feFuncB->setAttribute('tableValues', $tableB);
    }
}
