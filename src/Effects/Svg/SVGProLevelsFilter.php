<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;

use function LottiePHP\Utils\createSizedArray;
use function LottiePHP\Utils\createTag;

class SVGProLevelsFilter
{
    public ?\DOMElement $feFuncA;
    public ?\DOMElement $feFuncB;
    public ?\DOMElement $feFuncBComposed;
    public ?\DOMElement $feFuncG;
    public ?\DOMElement $feFuncGComposed;
    public ?\DOMElement $feFuncR;
    public ?\DOMElement $feFuncRComposed;
    public GroupEffect $filterManager;

    public function __construct(
        \DOMElement $filter,
        GroupEffect $filterManager,
        BaseElement|BaseProperty $_elem,
        string $id
    ) {
        $this->filterManager = $filterManager;
        $effectElements = $this->filterManager->effectElements;

        // if (!$effectElements) {
        //   throw new Error("{$this->constructor.name}: Missing Effect Elements")
        // }

        $feComponentTransfer = createTag('feComponentTransfer');

        // Red
        if (
            $effectElements[10]['p']['k']
            ?? 0 !== $effectElements[10]['p']['v']
            ?? $effectElements[11]['p']['k']
            ?? 1 !== $effectElements[11]['p']['v']
            ?? $effectElements[12]['p']['k']
            ?? 1 !== $effectElements[12]['p']['v']
            ?? $effectElements[13]['p']['k']
            ?? 0 !== $effectElements[13]['p']['v']
            ?? $effectElements[14]['p']['k']
            ?? 1 !== $effectElements[14]['p']['v']
        ) {
            $this->feFuncR = $this->createFeFunc('feFuncR', $feComponentTransfer);
        }
        // Green
        if (
            $effectElements[17]['p']['k']
            ?? 0 !== $effectElements[17]['p']['v']
            ?? $effectElements[18]['p']['k']
            ?? 1 !== $effectElements[18]['p']['v']
            ?? $effectElements[19]['p']['k']
            ?? 1 !== $effectElements[19]['p']['v']
            ?? $effectElements[20]['p']['k']
            ?? 0 !== $effectElements[20]['p']['v']
            ?? $effectElements[21]['p']['k']
            ?? 1 !== $effectElements[21]['p']['v']
        ) {
            $this->feFuncG = $this->createFeFunc('feFuncG', $feComponentTransfer);
        }
        // Blue
        if (
            $effectElements[24]['p']['k']
            ?? 0 !== $effectElements[24]['p']['v']
            ?? $effectElements[25]['p']['k']
            ?? 1 !== $effectElements[25]['p']['v']
            ?? $effectElements[26]['p']['k']
            ?? 1 !== $effectElements[26]['p']['v']
            ?? $effectElements[27]['p']['k']
            ?? 0 !== $effectElements[27]['p']['v']
            ?? $effectElements[28]['p']['k']
            ?? 1 !== $effectElements[28]['p']['v']
        ) {
            $this->feFuncB = $this->createFeFunc('feFuncB', $feComponentTransfer);
        }
        // Alpha
        if (
            $effectElements[31]['p']['k']
            ?? 0 !== $effectElements[31]['p']['v']
            ?? $effectElements[32]['p']['k']
            ?? 1 !== $effectElements[32]['p']['v']
            ?? $effectElements[33]['p']['k']
            ?? 1 !== $effectElements[33]['p']['v']
            ?? $effectElements[34]['p']['k']
            ?? 0 !== $effectElements[34]['p']['v']
            ?? $effectElements[35]['p']['k']
            ?? 1 !== $effectElements[35]['p']['v']
        ) {
            $this->feFuncA = $this->createFeFunc('feFuncA', $feComponentTransfer);
        }
        // RGB
        if ($this->feFuncR || $this->feFuncG || $this->feFuncB || $this->feFuncA) {
            $feComponentTransfer->setAttribute('color-interpolation-filters', 'sRGB');
            $filter->appendChild($feComponentTransfer);
        }

        if (
            $effectElements[3]['p']['k']
            ?? 0 !== $effectElements[3]['p']['v']
            ?? $effectElements[4]['p']['k']
            ?? 1 !== $effectElements[4]['p']['v']
            ?? $effectElements[5]['p']['k']
            ?? 1 !== $effectElements[5]['p']['v']
            ?? $effectElements[6]['p']['k']
            ?? 0 !== $effectElements[6]['p']['v']
            ?? $effectElements[7]['p']['k']
            ?? 1 !== $effectElements[7]['p']['v']
        ) {
            $feComponentTransfer = createTag('feComponentTransfer');
            $feComponentTransfer->setAttribute('color-interpolation-filters', 'sRGB');
            $feComponentTransfer->setAttribute('result', $id);
            $filter->appendChild($feComponentTransfer);
            $this->feFuncRComposed = $this->createFeFunc('feFuncR', $feComponentTransfer);
            $this->feFuncGComposed = $this->createFeFunc('feFuncG', $feComponentTransfer);
            $this->feFuncBComposed = $this->createFeFunc('feFuncB', $feComponentTransfer);
        }
    }

    public function createFeFunc(
        string $type,
        \DOMElement $feComponentTransfer
    ) {
        $feFunc = createTag($type);

        $feFunc->setAttribute('type', 'table');
        $feComponentTransfer->appendChild($feFunc);

        return $feFunc;
    }

    public function getTableValue(
        float $inputBlack,
        float $inputWhite,
        float $gamma,
        float $outputBlack,
        float $outputWhite
    ) {
        $cnt = 0;
        $segments = 256;
        $perc = null;
        $min = min($inputBlack, $inputWhite);
        $max = max($inputBlack, $inputWhite);
        $table = createSizedArray($segments);
        $colorValue = null;
        $pos = 0;
        $outputDelta = $outputWhite - $outputBlack;
        $inputDelta = $inputWhite - $inputBlack;

        while ($cnt <= 256) {
            $perc = $cnt / 256;
            if ($perc <= $min) {
                $colorValue = $inputDelta < 0 ? $outputWhite : $outputBlack;
            } elseif ($perc >= $max) {
                $colorValue = $inputDelta < 0 ? $outputBlack : $outputWhite;
            } else {
                $colorValue
                  = $outputBlack
                  + $outputDelta * pow(($perc - $inputBlack) / $inputDelta, 1 / $gamma);
            }
            $table[$pos] = $colorValue;
            ++$pos;
            $cnt += 256 / ($segments - 1);
        }

        return implode(' ', $table);
    }

    public function renderFrame(?bool $forceRender = null)
    {
        if (
            !$forceRender && !$this->filterManager->_mdf
        ) {
            return;
        }
        $val = null;
        $effectElements = $this->filterManager->effectElements;

        if (
            $this->feFuncRComposed
            && ($forceRender
              || $effectElements[3]['p']['_mdf']
              || $effectElements[4]['p']['_mdf']
              || $effectElements[5]['p']['_mdf']
              || $effectElements[6]['p']['_mdf']
              || $effectElements[7]['p']['_mdf'])
        ) {
            $val = $this->getTableValue(
                $effectElements[3]['p']['v'],
                $effectElements[4]['p']['v'],
                $effectElements[5]['p']['v'],
                $effectElements[6]['p']['v'],
                $effectElements[7]['p']['v']
            );
            $this->feFuncRComposed->setAttribute('tableValues', $val);
            $this->feFuncGComposed?->setAttribute('tableValues', $val);
            $this->feFuncBComposed?->setAttribute('tableValues', $val);
        }

        if (
            $this->feFuncR
            && ($forceRender
              || $effectElements[10]['p']['_mdf']
              || $effectElements[11]['p']['_mdf']
              || $effectElements[12]['p']['_mdf']
              || $effectElements[13]['p']['_mdf']
              || $effectElements[14]['p']['_mdf'])
        ) {
            $val = $this->getTableValue(
                $effectElements[10]['p']['v'],
                $effectElements[11]['p']['v'],
                $effectElements[12]['p']['v'],
                $effectElements[13]['p']['v'],
                $effectElements[14]['p']['v']
            );
            $this->feFuncR->setAttribute('tableValues', $val);
        }

        if (
            $this->feFuncG
            && ($forceRender
              || $effectElements[17]['p']['_mdf']
              || $effectElements[18]['p']['_mdf']
              || $effectElements[19]['p']['_mdf']
              || $effectElements[20]['p']['_mdf']
              || $effectElements[21]['p']['_mdf'])
        ) {
            $val = $this->getTableValue(
                $effectElements[17]['p']['v'],
                $effectElements[18]['p']['v'],
                $effectElements[19]['p']['v'],
                $effectElements[20]['p']['v'],
                $effectElements[21]['p']['v']
            );
            $this->feFuncG->setAttribute('tableValues', $val);
        }

        if (
            $this->feFuncB
            && ($forceRender
              || $effectElements[24]['p']['_mdf']
              ?? $effectElements[25]['p']['_mdf']
              ?? $effectElements[26]['p']['_mdf']
              ?? $effectElements[27]['p']['_mdf']
              ?? $effectElements[28]['p']['_mdf'])
        ) {
            $val = $this->getTableValue(
                $effectElements[24]['p']['v'],
                $effectElements[25]['p']['v'],
                $effectElements[26]['p']['v'],
                $effectElements[27]['p']['v'],
                $effectElements[28]['p']['v']
            );
            $this->feFuncB->setAttribute('tableValues', $val);
        }

        if (
            $this->feFuncA
            && ($forceRender
              || $effectElements[31]['p']['_mdf']
              ?? $effectElements[32]['p']['_mdf']
              ?? $effectElements[33]['p']['_mdf']
              ?? $effectElements[34]['p']['_mdf']
              ?? $effectElements[35]['p']['_mdf'])
        ) {
            $val = $this->getTableValue(
                $effectElements[31]['p']['v'],
                $effectElements[32]['p']['v'],
                $effectElements[33]['p']['v'],
                $effectElements[34]['p']['v'],
                $effectElements[35]['p']['v']
            );
            $this->feFuncA->setAttribute('tableValues', $val);
        }
    }
}
