<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Properties\BaseProperty;

use function LottiePHP\Utils\createTag;
use function LottiePHP\Utils\rgbToHex;

class SVGDropShadowEffect extends SVGComposableEffect
{
    public \DOMElement $feFlood;
    public \DOMElement $feGaussianBlur;
    public \DOMElement $feOffset;
    public GroupEffect $filterManager;

    public function __construct(
        \DOMElement $filter,
        GroupEffect $filterManager,
        BaseElement|BaseProperty $_elem,
        string $id,
        string $source
    ) {
        $globalFilterSize = $filterManager->container['globalData']['renderConfig']['filterSize'] ?? 0;
        $filterSize = $filterManager->data['fs']
          ?? $globalFilterSize ?? [
              'height' => '100%',
              'width' => '100%',
              'x' => '0%',
              'y' => '0%',
          ];

        $filter->setAttribute('x', $filterSize['x']);
        $filter->setAttribute('y', $filterSize['y']);
        $filter->setAttribute('width', $filterSize['width']);
        $filter->setAttribute('height', $filterSize['height']);
        $this->filterManager = $filterManager;

        $feGaussianBlur = createTag('feGaussianBlur');

        $feGaussianBlur->setAttribute('in', 'SourceAlpha');
        $feGaussianBlur->setAttribute('result', "{$id}_drop_shadow_1");
        $feGaussianBlur->setAttribute('stdDeviation', '0');
        $this->feGaussianBlur = $feGaussianBlur;
        $filter->appendChild($feGaussianBlur);

        $feOffset = createTag('feOffset');

        $feOffset->setAttribute('dx', 25);
        $feOffset->setAttribute('dy', 0);
        $feOffset->setAttribute('in', "{$id}_drop_shadow_1");
        $feOffset->setAttribute('result', "{$id}_drop_shadow_2");
        $this->feOffset = $feOffset;
        $filter->appendChild($feOffset);
        $feFlood = createTag('feFlood');

        $feFlood->setAttribute('flood-color', '#00ff00');
        $feFlood->setAttribute('flood-opacity', '1');
        $feFlood->setAttribute('result', "{$id}_drop_shadow_3");
        $this->feFlood = $feFlood;
        $filter->appendChild($feFlood);

        $feComposite = createTag('feComposite');

        $feComposite->setAttribute('in', "{$id}_drop_shadow_3");
        $feComposite->setAttribute('in2', "{$id}_drop_shadow_2");
        $feComposite->setAttribute('operator', 'in');
        $feComposite->setAttribute('result', "{$id}_drop_shadow_4");
        $filter->appendChild($feComposite);

        $feMerge = $this->createMergeNode($id, ["{$id}_drop_shadow_4", $source]);

        $filter->appendChild($feMerge);
    }

    public function renderFrame(?bool $forceRender)
    {
        if (
            !$forceRender && !$this->filterManager->_mdf
        ) {
            return;
        }
        if ($forceRender || $this->filterManager->effectElements[4]['p']['_mdf'] ?? null) {
            $stdDeviation = ($this->filterManager->effectElements[4]['p']['v'] ?? 0) / 4;

            $this->feGaussianBlur->setAttribute('stdDeviation', "{$stdDeviation} {$stdDeviation}");
        }
        if ($forceRender || $this->filterManager->effectElements[0]['p']['_mdf'] ?? null) {
            $col = $this->filterManager->effectElements[0]['p']['v'] ?? [0, 0, 0];

            $this->feFlood->setAttribute(
                'flood-color',
                rgbToHex(
                    round($col[0] * 255),
                    round($col[1] * 255),
                    round($col[2] * 255)
                )
            );
        }
        if ($forceRender || $this->filterManager->effectElements[1]['p']['_mdf'] ?? null) {
            $this->feFlood->setAttribute(
                'flood-opacity',
                ($this->filterManager->effectElements[1]['p']['v'] ?? 0) / 255
            );
        }
        if (
            $forceRender
            || $this->filterManager->effectElements[2]['p']['_mdf']
            ?? $this->filterManager->effectElements[3]['p']['_mdf'] ?? null
        ) {
            $distance = $this->filterManager->effectElements[3]['p']['v'] ?? null;
            $angle
              = (($this->filterManager->effectElements[2]['p']['v'] ?? null) - 90) * Constants::$degToRads;
            $x = $distance * cos($angle);
            $y = $distance * sin($angle);

            $this->feOffset->setAttribute('dx', $x);
            $this->feOffset->setAttribute('dy', $y);
        }
    }
}
