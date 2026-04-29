<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;

use function LottiePHP\Utils\createTag;

class SVGGaussianBlurEffect
{
    public \DOMElement $feGaussianBlur;
    public GroupEffect $filterManager;

    public function __construct(
        \DOMElement $filter,
        GroupEffect $filterManager,
        BaseElement|BaseProperty $_elem,
        string $id
    ) {
        // Outset the filter region by 100% on all sides to accommodate blur expansion.
        $filter->setAttribute('x', '-100%');
        $filter->setAttribute('y', '-100%');
        $filter->setAttribute('width', '300%');
        $filter->setAttribute('height', '300%');

        $this->filterManager = $filterManager;
        $feGaussianBlur = createTag('feGaussianBlur');

        $feGaussianBlur->setAttribute('result', $id);
        $filter->appendChild($feGaussianBlur);
        $this->feGaussianBlur = $feGaussianBlur;
    }

    public function renderFrame(?bool $forceRender)
    {
        if (
            !$forceRender && !$this->filterManager->_mdf
            // !$this->filterManager->effectElements
        ) {
            return;
        }
        // Empirical value, matching AE's blur appearance.
        $kBlurrinessToSigma = 0.3;
        $sigma
            = ($this->filterManager->effectElements[0]->p->v ?? 0)
            * $kBlurrinessToSigma;
        // Dimensions mapping:
        //
        //   1 -> horizontal & vertical
        //   2 -> horizontal only
        //   3 -> vertical only
        $dimensions = (int) $this->filterManager->effectElements[1]->p->v;
        $sigmaX = 3 === $dimensions ? 0 : $sigma;
        $sigmaY = 2 === $dimensions ? 0 : $sigma;

        $this->feGaussianBlur->setAttribute('stdDeviation', "{$sigmaX} {$sigmaY}");

        // Repeat edges mapping:
        //
        //   0 -> off -> duplicate
        //   1 -> on  -> wrap
        $edgeMode
          = 1 === (float) ($this->filterManager->effectElements[2]->p->v ?? 0)
            ? 'wrap'
            : 'duplicate';

        $this->feGaussianBlur->setAttribute('edgeMode', $edgeMode);
    }
}
