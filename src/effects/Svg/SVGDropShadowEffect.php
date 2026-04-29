<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\Properties\MultiDimensionalProperty;
use LottiePHP\Utils\Properties\ValueProperty;

use function LottiePHP\Utils\createTag;
use function LottiePHP\Utils\rgbToHex;

class SVGDropShadowEffect extends SVGComposableEffect
{
    public \DOMElement $feFlood;
    public \DOMElement $feGaussianBlur;
    public \DOMElement $feOffset;
    public GroupEffect $filterManager;

    private function getEffectProp(int $index): MultiDimensionalProperty|ValueProperty|null
    {
        $eff = $this->filterManager->effectElements[$index] ?? null;
        if (!$eff) {
            return null;
        }

        // Old shape (arrays): ['p' => ['v' => ..., '_mdf' => ...]]
        if (is_array($eff)) {
            $p = $eff['p'] ?? null;
            if (is_object($p) && ($p instanceof MultiDimensionalProperty || $p instanceof ValueProperty)) {
                return $p;
            }
            if (is_array($p)) {
                // Best-effort shim (avoid fatal errors); treat as no prop.
                return null;
            }

            return null;
        }

        // New shape (objects): $eff->p is a Property instance.
        if (is_object($eff) && property_exists($eff, 'p')) {
            $p = $eff->p;
            if ($p instanceof MultiDimensionalProperty || $p instanceof ValueProperty) {
                return $p;
            }
        }

        // Some effect classes implement ArrayAccess for '$eff["p"]'.
        if ($eff instanceof \ArrayAccess) {
            $p = $eff['p'] ?? null;
            if ($p instanceof MultiDimensionalProperty || $p instanceof ValueProperty) {
                return $p;
            }
        }

        return null;
    }

    public function __construct(
        \DOMElement $filter,
        GroupEffect $filterManager,
        BaseElement|BaseProperty $_elem,
        string $id,
        string $source
    ) {
        $globalFilterSize = $filterManager->container['globalData']['renderConfig']['filterSize'] ?? null;
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
        $blur = $this->getEffectProp(4);
        if ($forceRender || ($blur?->_mdf ?? null)) {
            $stdDeviation = (($blur?->v ?? 0) ?: 0) / 4;

            $this->feGaussianBlur->setAttribute('stdDeviation', "{$stdDeviation} {$stdDeviation}");
        }
        $color = $this->getEffectProp(0);
        if ($forceRender || ($color?->_mdf ?? null)) {
            $col = $color?->v ?? [0, 0, 0];
            if (!is_array($col) || count($col) < 3) {
                $col = [0, 0, 0];
            }

            $this->feFlood->setAttribute(
                'flood-color',
                rgbToHex(
                    round($col[0] * 255),
                    round($col[1] * 255),
                    round($col[2] * 255)
                )
            );
        }
        $opacity = $this->getEffectProp(1);
        if ($forceRender || ($opacity?->_mdf ?? null)) {
            $this->feFlood->setAttribute(
                'flood-opacity',
                (($opacity?->v ?? 0) ?: 0) / 255
            );
        }
        $angleProp = $this->getEffectProp(2);
        $distanceProp = $this->getEffectProp(3);
        if ($forceRender || ($angleProp?->_mdf ?? null) || ($distanceProp?->_mdf ?? null)) {
            $distance = ($distanceProp?->v ?? 0) ?: 0;
            $angle = ((($angleProp?->v ?? 0) ?: 0) - 90) * Constants::$degToRads;
            $x = $distance * cos($angle);
            $y = $distance * sin($angle);

            $this->feOffset->setAttribute('dx', $x);
            $this->feOffset->setAttribute('dy', $y);
        }
    }
}
