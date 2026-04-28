<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\LocationHref;
use LottiePHP\Utils\Properties\BaseProperty;

use function LottiePHP\Utils\createElementID;
use function LottiePHP\Utils\createNS;
use function LottiePHP\Utils\toRGBString;

class SVGStrokeEffect
{
    public BaseElement|BaseProperty $elem;
    public GroupEffect $filterManager;
    public $initialized = false;
    public ?\DOMElement $masker;
    public ?\DOMElement $pathMasker;
    public $paths = [];

    public function __construct(
        \DOMElement $_fil,
        GroupEffect $filterManager,
        BaseElement|BaseProperty $elem
    ) {
        $this->filterManager = $filterManager;
        $this->elem = $elem;
    }

    public function initialize()
    {
        // if (!$this->filterManager->effectElements) {
        //   throw new Error("${$this->constructor.name}: Missing Effects Elements")
        // }
        $elemChildren = [...$this->elem->layerElement?->children
          ?? $this->elem->layerElement?->childNodes
          ?? []];
        $path = null;
        $i = null;
        $len = null;

        if (1 === $this->filterManager->effectElements[1]['p']['v']) {
            $len = count($this->elem->maskManager?->masksProperties ?? []);
            $i = 0;
        } else {
            $i = $this->filterManager->effectElements[0]['p']['v'] - 1;
            $len = $i + 1;
        }
        $groupPath = createNS('g');

        $groupPath->setAttribute('fill', 'none');
        $groupPath->setAttribute('stroke-linecap', 'round');
        $groupPath->setAttribute('stroke-dashoffset', '1');
        for ($i; $i < $len; ++$i) {
            $path = createNS('path');
            $groupPath->appendChild($path);
            array_push($this->paths, [
                'm' => $i,
                'p' => $path,
            ]);
        }
        if (3 === $this->filterManager->effectElements[10]['p']['v']) {
            $mask = createNS('mask');
            $id = createElementID();

            $mask->setAttribute('id', $id);
            $mask->setAttribute('mask-type', 'alpha');
            $mask->appendChild($groupPath);
            $this->elem->globalData['defs']->appendChild($mask);
            $g = createNS('g');

            $locationHref = LocationHref::getHref();
            $g->setAttribute('mask', "url({$locationHref}#{$id})");
            while ($elemChildren[0]) {
                $g->appendChild($elemChildren[0]);
            }
            $this->elem->layerElement?->appendChild($g);
            $this->masker = $mask;
            $groupPath->setAttribute('stroke', '#fff');
        } elseif (
            1 === $this->filterManager->effectElements[10]['p']['v']
            || 2 === $this->filterManager->effectElements[10]['p']['v']
        ) {
            if (2 === $this->filterManager->effectElements[10]['p']['v']) {
                $elemChildren = [...$this->elem->layerElement?->children
                ?? $this->elem->layerElement?->childNodes
                ?? []];
                while (count($elemChildren) > 0) {
                    $this->elem->layerElement?->removeChild($elemChildren[0]);
                }
            }
            $this->elem->layerElement?->appendChild($groupPath);
            $this->elem->layerElement?->removeAttribute('mask');
            $groupPath->setAttribute('stroke', '#fff');
        }
        $this->initialized = true;
        $this->pathMasker = $groupPath;
    }

    public function renderFrame(?bool $forceRender)
    {
        // if (!$this->filterManager->effectElements) {
        //   throw new Error("${$this->constructor.name}: Missing Effect element")
        // }
        if (!$this->initialized) {
            $this->initialize();
        }
        $length = count($this->paths);
        $mask = null;
        $path = null;

        for ($i = 0; $i < $length; ++$i) {
            if (($this->paths[$i]['m'] ?? null) === -1) {
                continue;
            }
            $mask = $this->elem->maskManager?->viewData[$this->paths[$i]['m'] ?? 0];
            $path = $this->paths[$i]['p'] ?? null;
            if (
                $mask
                && ($forceRender || $this->filterManager->_mdf || ($mask['prop']['_mdf'] ?? null))
            ) {
                $path?->setAttribute('d', $mask['lastPath']);
            }
            if (
                $forceRender
                || $this->filterManager->effectElements[9]['p']['_mdf']
                ?? $this->filterManager->effectElements[4]['p']['_mdf']
                ?? $this->filterManager->effectElements[7]['p']['_mdf']
                ?? $this->filterManager->effectElements[8]['p']['_mdf']
                ?? $mask['prop']['_mdf'] ?? null
            ) {
                $dasharrayValue = null;

                if (
                    0 !== $this->filterManager->effectElements[7]['p']['v']
                    || 100 !== $this->filterManager->effectElements[8]['p']['v']
                ) {
                    $s
                      = min(
                          $this->filterManager->effectElements[7]['p']['v'],
                          $this->filterManager->effectElements[8]['p']['v']
                      ) * 0.01;
                    $e
                      = max(
                          $this->filterManager->effectElements[7]['p']['v'],
                          $this->filterManager->effectElements[8]['p']['v']
                      ) * 0.01;
                    $l = $path?->getTotalLength() ?? 0;

                    $lsVal = $l * $s;

                    $dasharrayValue = "0 0 0 {$lsVal} ";
                    $lineLength = $l * ($e - $s);
                    $segment
                      = 1
                      + $this->filterManager->effectElements[4]['p']['v']
                      * 2
                      * $this->filterManager->effectElements[9]['p']['v']
                      * 0.01;
                    $units = floor($lineLength / $segment);

                    for ($j = 0; $j < $units; ++$j) {
                        $dasharrayValue .= '1 '.$this->filterManager->effectElements[4]['p']['v']
                        * 2
                        * $this->filterManager->effectElements[9]['p']['v']
                        * 0.01.' ';
                    }
                    $l10 = $l * 10;
                    $dasharrayValue .= "0 {$l10} 0 0";
                } else {
                    $dasharrayValue = '1 '.$this->filterManager->effectElements[4]['p']['v']
                    * 2
                    * $this->filterManager->effectElements[9]['p']['v']
                    * 0.01;
                }
                $path?->setAttribute('stroke-dasharray', $dasharrayValue);
            }
        }
        if ($forceRender || $this->filterManager->effectElements[4]['p']['_mdf']) {
            $this->pathMasker?->setAttribute(
                'stroke-width',
                $this->filterManager->effectElements[4]['p']['v'] * 2
            );
        }

        if ($forceRender || $this->filterManager->effectElements[6]['p']['_mdf']) {
            $this->pathMasker?->setAttribute(
                'opacity',
                (float) $this->filterManager->effectElements[6]['p']['v']
            );
        }
        if ((1 === $this->filterManager->effectElements[10]['p']['v']
          || 2 === $this->filterManager->effectElements[10]['p']['v'])
          && ($forceRender || $this->filterManager->effectElements[3]['p']['_mdf'])
        ) {
            $this->pathMasker?->setAttribute(
                'stroke',
                toRGBString($this->filterManager->effectElements[3]['p']['v'])
            );
        }
    }
}
