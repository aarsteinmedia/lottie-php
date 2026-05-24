<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;

use function LottiePHP\Utils\createElementID;
use function LottiePHP\Utils\createTag;

class SVGMatte3Effect
{
    public BaseElement|BaseProperty $elem;
    public \DOMElement $filterElem;
    public GroupEffect $filterManager;
    public bool $initialized = false;

    public function __construct(
        \DOMElement $filterElem,
        GroupEffect $filterManager,
        BaseElement|BaseProperty $elem
    ) {
        $this->filterManager = $filterManager;
        $this->filterElem = $filterElem;
        $this->elem = $elem;
        $elem->matteElement = createTag('g');
        if ($elem->layerElement) {
            $elem->matteElement->appendChild($elem->layerElement);
        }
        if ($elem->transformedElement) {
            $elem->matteElement->appendChild($elem->transformedElement);
        }
        $elem->baseElement = $elem->matteElement;
    }

    public function findSymbol(BaseElement|BaseProperty $mask)
    {
        $i = 0;
        $length = count($this->_svgMatteSymbols);

        while ($i < $length) {
            if ($this->_svgMatteSymbols[$i] === $mask) {
                return $this->_svgMatteSymbols[$i];
            }
            ++$i;
        }

        return null;
    }

    public function initialize()
    {
        $ind = EffectProp::value($this->filterManager, 0);
        $elements = $this->elem->comp?->elements ?? [];
        $i = 0;
        $length = count($elements);

        while ($i < $length) {
            $element = $elements[$i];

            if ($element && $element->data['ind'] === $ind) {
                $this->setElementAsMask($this->elem, $element);
            }
            ++$i;
        }
        $this->initialized = true;
    }

    public function renderFrame(?float $_val = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
    }

    public function replaceInParent(BaseElement|BaseProperty $mask, string $symbolId)
    {
        $parentNode = $mask->layerElement?->parentNode;

        if (!$parentNode) {
            return;
        }
        $children = iterator_to_array($parentNode->childNodes);
        $i = 0;
        $length = count($children);

        while ($i < $length) {
            if ($children[$i] === $mask->layerElement) {
                break;
            }
            ++$i;
        }
        $nextChild = null;

        if ($i <= $length - 2) {
            $nextChild = $children[$i + 1];
        }
        $useElem = createTag('use');

        $useElem->setAttribute('href', "#{$symbolId}");
        if ($nextChild) {
            $parentNode->insertBefore($useElem, $nextChild);
        } else {
            $parentNode->appendChild($useElem);
        }
    }

    public function setElementAsMask(
        BaseElement|BaseProperty $elem,
        BaseElement|BaseProperty $mask
    ) {
        if (!$this->findSymbol($mask)) {
            $symbolId = createElementID();
            $masker = createTag('mask');

            if ($mask->layerId) {
                $masker->id = $mask->layerId;
            }

            $masker->setAttribute('mask-type', 'alpha');
            array_push($this->_svgMatteSymbols, $mask);
            $defs = $elem->globalData['defs'] ?? null;

            $defs?->appendChild($masker);
            $symbol = createTag('symbol');

            $symbol->setAttribute('id', $symbolId);
            $this->replaceInParent($mask, $symbolId);
            if ($mask->layerElement) {
                $symbol->appendChild($mask->layerElement);
            }

            $defs?->appendChild($symbol);
            $useElem = createTag('use');

            $useElem->setAttribute('href', "#{$symbolId}");
            $masker->appendChild($useElem);
            $mask->data['hd'] = false;

            $mask->show();
        }
        $elem->setMatte($mask->layerId ?? '');
    }

    private array $_svgMatteSymbols = [];
}
