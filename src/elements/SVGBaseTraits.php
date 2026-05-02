<?php

namespace LottiePHP\Elements;

use LottiePHP\Utils\FeatureSupport;
use LottiePHP\Utils\FiltersFactory;
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

            if ($this->layerId ?? null) {
                $gg->setAttribute('id', $this->layerId);
            }
            $debugAttrs = (bool) ($this->globalData['renderConfig']['debugAttributes'] ?? false);
            // Put layer debug mapping on the wrapper too (this wrapper is what the matte <use>
            // references, and it is where transforms are applied for track mattes).
            if ($debugAttrs) {
                if ($this->data['ind'] ?? null) {
                    $gg->setAttribute('data-lottie-ind', (string) $this->data['ind']);
                }
                if ($this->data['nm'] ?? null) {
                    $gg->setAttribute('data-lottie-nm', (string) $this->data['nm']);
                }
                if (array_key_exists('ty', $this->data ?? [])) {
                    $gg->setAttribute('data-lottie-ty', (string) ($this->data['ty'] ?? ''));
                }
                if ($this->data['refId'] ?? null) {
                    $gg->setAttribute('data-lottie-refId', (string) $this->data['refId']);
                }
                $gg->setAttribute('data-lottie-matte-src', '1');
            }
            $gg->appendChild($this->layerElement);

            // Track matte sources are referenced via the wrapper `<g id="$layerId">` in `<defs>`
            // (see `getMatte()` using `<use href="#$layerId">`). Apply transforms to the wrapper,
            // not the inner layer element, otherwise parenting transforms (scale/rotation) won't
            // affect the actual matte geometry.
            // But DO NOT render the matte source into the main scene graph: it should only
            // exist in `<defs>` and be referenced by the following layer via `<mask><use/></mask>`.
            $this->baseElement = null;
            $this->transformedElement = $gg;
            $this->maskedElement = $gg;

            $layerElementParent = $gg;
            $this->globalData['defs']->appendChild($gg);
        } elseif ($this->data['tt'] ?? null) {
            $this->matteElement->appendChild($this->layerElement);

            $layerElementParent = $this->matteElement;
            $this->baseElement = $this->matteElement;
        } else {
            $this->baseElement = $this->layerElement;
        }
        if ($this->data['ln'] ?? null) {
            $this->layerElement->setAttribute('id', $this->data['ln']);
        }
        if ($this->data['cl'] ?? null) {
            addClasses($this->layerElement, [$this->data['cl']]);
        }
        $debugAttrs = (bool) ($this->globalData['renderConfig']['debugAttributes'] ?? false);
        // Debug aid: keep a stable mapping from SVG nodes to Lottie layers.
        // Disabled by default; enable via rendererSettings.debugAttributes=true.
        if ($debugAttrs) {
            if ($this->data['ind'] ?? null) {
                $this->layerElement->setAttribute('data-lottie-ind', (string) $this->data['ind']);
            }
            if ($this->data['nm'] ?? null) {
                $this->layerElement->setAttribute('data-lottie-nm', (string) $this->data['nm']);
            }
            if (array_key_exists('ty', $this->data ?? [])) {
                $this->layerElement->setAttribute('data-lottie-ty', (string) ($this->data['ty'] ?? ''));
            }
            if ($this->data['refId'] ?? null) {
                $this->layerElement->setAttribute('data-lottie-refId', (string) $this->data['refId']);
            }
        }

        /*
         * Clipping compositions to hide content that exceeds boundaries.
         * If collapsed transformations is on, component should not be clipped.
         *
         * Only real precomposition layers (ty === 0) carry `ty`. Embedded shape payloads such as
         * font glyph outlines omit `ty`; treating them as ty 0 would apply this clip with w/h = 0
         * and hide all paths (degenerate clip rect).
         */
        if (
            array_key_exists('ty', $this->data)
            && 0 === (int) ($this->data['ty'] ?? 0)
            && !($this->data['hd'] ?? false)
        ) {
            $cp = createTag('clipPath');
            $pt = createTag('path');

            $w = $this->data['w'] ?? 0;
            $h = $this->data['h'] ?? 0;

            $pt->setAttribute(
                'd',
                "M0,0 L{$w},0 L{$w},{$h} L0,{$h}z"
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

    public function getMatte(int $matteType = 1): string
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
                // Track mattes should behave like alpha masks (as in Lottie-web's SVG renderer).
                // Without this, some viewers fall back to luminance masking which can make
                // colored mattes (e.g. the 404 globe) hide content unexpectedly.
                $masker->setAttribute('mask-type', 3 === $matteType ? 'luminance' : 'alpha');
                $useElement = createTag('use');
                $useElement->setAttribute(
                    'href',
                    "#{$this->layerId}"
                );
                $masker->appendChild($useElement);
                $this->globalData['defs']?->appendChild($masker);
                if (FeatureSupport::$maskType || 1 !== $matteType) {
                    break;
                }
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
                $useElement->setAttribute(
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
