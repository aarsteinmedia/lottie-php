<?php

namespace LottiePHP\Elements;

use LottiePHP\Utils\FeatureSupport;
use LottiePHP\Utils\FiltersFactory;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\LocationHref;

use function LottiePHP\Utils\addClasses;
use function LottiePHP\Utils\createElementID;
use function LottiePHP\Utils\createTag;

trait SVGBaseTraits
{
    public ?bool $_sizeChanged;
    public ?array $matteMasks;

    public function createContainerElements()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }

        if (!$this->globalData) {
            throw new \Error(get_class($this).': globalData is not implemented');
        }

        if (!$this->layerElement) {
            throw new \Error(get_class($this).': layerElement is not implemented');
        }

        $this->matteElement = createTag('g');
        $this->transformedElement = $this->layerElement;
        $this->maskedElement = $this->layerElement;
        $this->_sizeChanged = false;
        $layerElementParent = null;

        // If this layer acts as a mask for the following layer
        if ($this->data['td'] ?? 0) {
            $this->matteMasks = [];
            $gg = createTag('g');

            if ($this->layerId) {
                $gg->setAttribute('id', $this->layerId);
            }
            $gg->appendChild($this->layerElement);

            $layerElementParent = $gg;
            $this->globalData['defs']->appendChild($gg);
        } elseif ($this->data['tt'] ?? null) {
            $this->matteElement->appendChild($this->layerElement);

            $layerElementParent = $this->matteElement;
            $this->baseElement = $this->matteElement;
        } else {
            $this->baseElement = $this->layerElement;
        }
        if (isset($this->data['ln'])) {
            $this->layerElement->setAttribute('id', $this->data['ln']);
        }
        if (isset($this->data['cl'])) {
            addClasses($this->layerElement, [$this->data['cl']]);
        }

        /*
         * Clipping compositions to hide content that exceeds boundaries.
         * If collapsed transformations is on, component should not be clipped.
         */
        if (0 === ($this->data['ty'] ?? 0) && !($this->data['hd'] ?? false)) {
            $cp = createTag('clipPath');
            $pt = createTag('path');

            $pt->setAttribute(
                'd',
                "M0,0 L{$this->data['w']},0 L{$this->data['w']},{$this->data['h']} L0,{$this->data['h']}z"
            );
            $clipId = createElementID();

            $cp->setAttribute('id', $clipId);
            $cp->appendChild($pt);
            $this->globalData['defs']->appendChild($cp);

            $locationHref = LocationHref::getHref();

            if ($this->checkMasks()) {
                $cpGroup = createTag('g');

                $cpGroup->setAttribute('clip-path', "url({$locationHref}#{$clipId})");
                $cpGroup->appendChild($this->layerElement);

                $this->transformedElement = $cpGroup;
                if ($layerElementParent) {
                    $layerElementParent->appendChild($this->transformedElement);
                } else {
                    $this->baseElement = $this->transformedElement;
                }
            } else {
                $this->layerElement->setAttribute(
                    'clip-path',
                    "url({$locationHref}#{$clipId})"
                );
            }
        }
        if (0 !== ($this->data['bm'] ?? null)) {
            $this->setBlendMode();
        }
    }

    public function createRenderableComponents()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': layerData is not initialized');
        }
        if (!$this->globalData) {
            throw new \Error(get_class($this).': globalData is not initialized');
        }
        $this->maskManager = new MaskElement(
            $this->data,
            $this,
            $this->globalData,
        );
        $this->renderableEffectsManager = new SVGEffects($this);
        $this->searchEffectTransforms();
    }

    public function destroyBaseElement()
    {
        $this->layerElement = null;
        $this->matteElement = null;
        $this->maskManager?->destroy();
    }

    public function getBaseElement(): ?\DOMElement
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        if (($this->data['hd'] ?? false) === true) {
            return null;
        }

        return $this->baseElement ?? null;
    }

    public function getMatte($matteType = 1): string
    {
        $this->matteMasks ??= [];
        if ($this->matteMasks[$matteType] ?? null) {
            return $this->matteMasks[$matteType];
        }
        $layerId = $this->layerId;
        $id = "{$layerId}_{$matteType}";
        $filId = null;
        $fil = null;
        $useElement = null;
        $gg = null;

        switch ($matteType) {
            case 1:
            case 3:
                $masker = createTag('mask');

                $masker->setAttribute('id', $id);
                $masker->setAttribute(
                    'mask-type',
                    3 === $matteType ? 'luminance' : 'alpha'
                );
                $useElement = createTag('use');
                $useElement->setAttributeNS(
                    Constants::$namespaceXlink,
                    'href',
                    "#{$this->layerId}"
                );
                $masker->appendChild($useElement);
                $this->globalData['defs']?->appendChild($masker);
                if (FeatureSupport::$maskType || 1 !== $matteType) {
                    break;
                }
                $masker->setAttribute('mask-type', 'luminance');
                $filId = createElementID();
                $fil = FiltersFactory::createFilter($filId);
                $this->globalData['defs']?->appendChild($fil);
                $fil->appendChild(FiltersFactory::createAlphaToLuminanceFilter());
                $gg = createTag('g');
                $gg->appendChild($useElement);
                $masker->appendChild($gg);
                $locationHref = LocationHref::getHref();
                $gg->setAttribute('filter', "url({$locationHref}#{$filId})");

                break;

            case 2:
                $maskGroup = createTag('mask');

                $maskGroup->setAttribute('id', $id);
                $maskGroup->setAttribute('mask-type', 'alpha');
                $maskGrouper = createTag('g');

                $maskGroup->appendChild($maskGrouper);
                $filId = createElementID();
                $fil = FiltersFactory::createFilter($filId);
                $feCTr = createTag('feComponentTransfer');

                $feCTr->setAttribute('in', 'SourceGraphic');
                $fil->appendChild($feCTr);
                $feFunc = createTag('feFuncA');

                $feFunc->setAttribute('type', 'table');
                $feFunc->setAttribute('tableValues', '1.0 0.0');
                $feCTr->appendChild($feFunc);
                $this->globalData['defs']?->appendChild($fil);
                $alphaRect = createTag('rect');

                $alphaRect->setAttribute('width', $this->comp['data']['w'] ?? 0);
                $alphaRect->setAttribute('height', $this->comp['data']['h'] ?? 0);
                $alphaRect->setAttribute('x', 0);
                $alphaRect->setAttribute('y', 0);

                $alphaRect->setAttribute('fill', '#ffffff');
                $alphaRect->setAttribute('opacity', '0');

                $locationHref = LocationHref::getHref();

                $maskGrouper->setAttribute('filter', "url({$locationHref}#{$filId})");
                $maskGrouper->appendChild($alphaRect);
                $useElement = createTag('use');
                $useElement->setAttributeNS(
                    Constants::$namespaceXlink,
                    'href',
                    "#{$this->layerId}"
                );
                $maskGrouper->appendChild($useElement);
                if (!FeatureSupport::$maskType) {
                    $maskGroup->setAttribute('mask-type', 'luminance');
                    $fil->appendChild(FiltersFactory::createAlphaToLuminanceFilter());
                    $gg = createTag('g');
                    $maskGrouper->appendChild($alphaRect);
                    if ($this->layerElement) {
                        $gg->appendChild($this->layerElement);
                    }

                    $maskGrouper->appendChild($gg);
                }
                $this->globalData['defs']?->appendChild($maskGroup);
        }
        $this->matteMasks[$matteType] = $id;

        return $this->matteMasks[$matteType];
    }

    public function initRendererElement()
    {
        $this->layerElement = createTag('g');
    }

    public function renderElement()
    {
        if (!$this->finalTransform) {
            throw new \Error(get_class($this).': finalTransform is not implemented');
        }
        if ($this->finalTransform['_localMatMdf']) {
            $this->transformedElement?->setAttribute(
                'transform',
                $this->finalTransform['localMat']->to2dCSS()
            );
        }
        if ($this->finalTransform['_opMdf']) {
            $this->transformedElement?->setAttribute(
                'opacity',
                $this->finalTransform['localOpacity']
            );
        }
    }

    public function setMatte(string $id)
    {
        if (!$this->matteElement) {
            return;
        }
        $locationHref = LocationHref::getHref();
        $this->matteElement->setAttribute('mask', "url({$locationHref}#{$id})");
    }
}
