<?php

namespace LottiePHP\Renderer;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Elements\ImageElement;
use LottiePHP\Elements\NullElement;
use LottiePHP\Elements\SolidElement;
use LottiePHP\Elements\SVGShapeElement;
use LottiePHP\Elements\SVGTextLottieElement;
use LottiePHP\Utils\Helpers\Constants;

use function LottiePHP\Utils\addClasses;
use function LottiePHP\Utils\addStyles;
use function LottiePHP\Utils\createElementID;
use function LottiePHP\Utils\createNS;
use function LottiePHP\Utils\createSizedArray;

trait SVGRendererTraits
{
    public function appendElementInPos(BaseElement $element, int $pos)
    {
        $newElement = $element->getBaseElement();

        if (!$newElement) {
            return;
        }
        $i = 0;
        $nextElement = null;

        while ($i < $pos) {
            if (true !== $this->elements[$i] && $this->elements[$i]?->getBaseElement()) {
                $nextElement = $this->elements[$i]?->getBaseElement();
            }
            ++$i;
        }
        if ($nextElement) {
            $this->layerElement?->insertBefore($newElement, $nextElement);

            return;
        }
        $this->layerElement?->appendChild($newElement);
    }

    public function buildItem(int $pos)
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).': globalData it not implemented');
        }

        if ($this->elements[$pos] || 99 === $this->layers[$pos]['ty']) {
            return;
        }

        $this->elements[$pos] = true;

        $element = $this->createItem($this->layers[$pos]);

        $this->elements[$pos] = $element;
        // if (getExpressionsPlugin()) {
        //     if ($this->layers[$pos]['ty'] === 0) {
        //         $this->globalData['projectInterface']['registerComposition']($element);
        //     }
        //     $element->initExpressions();
        // }
        $this->appendElementInPos($element, $pos);
        if (isset($this->layers[$pos]['tt'])) {
            $elementIndex
              = array_key_exists('tp', $this->layers[$pos])
                ? $this->findIndexByInd($this->layers[$pos]['tp'])
                : $pos - 1;

            if (-1 === $elementIndex) {
                return;
            }
            if (
                !$this->elements[$elementIndex]
                || true === $this->elements[$elementIndex]
            ) {
                $this->buildItem($elementIndex);
                $this->addPendingElement($element);

                return;
            }
            $matteElement = $this->elements[$elementIndex];
            $matteMask = $matteElement->getMatte($this->layers[$pos]['tt']);

            $element->setMatte($matteMask);
        }
    }

    public function checkPendingElements()
    {
        while (count($this->pendingElements) > 0) {
            $element = array_pop($this->pendingElements);

            $element->checkParenting();

            if ($element->data['tt']) {
                $i = 0;
                $length = count($this->elements);

                while ($i < $length) {
                    if ($this->elements[$i] !== $element) {
                        ++$i;

                        continue;
                    }
                    $elementIndex = array_key_exists('tp', $element->data)
                        ? $this->findIndexByInd($element->data['tp'])
                        : $i - 1;
                    $matteMask = $this->elements[$elementIndex]->getMatte($this->layers[$i]['tt']) ?? '';

                    $element->setMatte($matteMask);

                    break;
                }
            }
        }
    }

    public function configAnimation(array $animData)
    {
        try {
            if (!$this->animationItem) {
                throw new \Error(get_class($this).": Can't access animationItem");
            }
            if (!$this->globalData) {
                throw new \Error(get_class($this).": Can't access globalData");
            }
            if (!$this->renderConfig) {
                throw new \Error(get_class($this).": Can't access renderConfig");
            }
            if (!$this->svgElement) {
                throw new \Error(get_class($this).": Can't access svgElement");
            }

            $this->svgElement->setAttribute('xmlns', Constants::$namespaceXlink);
            if ($this->renderConfig['viewBoxSize']) {
                $this->svgElement->setAttribute(
                    'viewBox',
                    $this->renderConfig['viewBoxSize']
                );
            } else {
                $this->svgElement->setAttribute('viewBox', "0 0 {$animData['w']} {$animData['h']}");
            }

            if (!$this->renderConfig['viewBoxOnly']) {
                $this->svgElement->setAttribute('width', $animData['w']);
                $this->svgElement->setAttribute('height', $animData['h']);
                addStyles(
                    $this->svgElement,
                    [
                        'width' => '100%',
                        'height' => '100%',
                        'transform' => 'translate3d(0,0,0)',
                    ]
                );
                if ($this->renderConfig['contentVisibility']) {
                    addStyles(
                        $this->svgElement,
                        [
                            'contentVisibility' => $this->renderConfig['contentVisibility'],
                        ]
                    );
                }
            }
            if ($this->renderConfig['width']) {
                $this->svgElement->setAttribute('width', $this->renderConfig['width']);
            }
            if ($this->renderConfig['height']) {
                $this->svgElement->setAttribute('height', $this->renderConfig['height']);
            }
            if ($this->renderConfig['className']) {
                addClasses($this->svgElement, [$this->renderConfig['className']]);
            }
            if ($this->renderConfig['id']) {
                $this->svgElement->setAttribute('id', $this->renderConfig['id']);
            }
            if (null !== $this->renderConfig['focusable']) {
                $this->svgElement->setAttribute(
                    'focusable',
                    $this->renderConfig['focusable']
                );
            }
            if ($this->renderConfig['preserveAspectRatio']) {
                $this->svgElement->setAttribute(
                    'preserveAspectRatio',
                    $this->renderConfig['preserveAspectRatio']
                );
            }

            $this->animationItem->wrapper?->appendChild($this->svgElement);

            // Mask animation
            $defs = $this->globalData['defs'];

            $this->setupGlobalData($animData, $defs);
            $this->globalData['progressiveLoad'] = $this->renderConfig['progressiveLoad'];
            $this->data = $animData;

            $maskElement = createNS('clipPath');
            $rect = createNS('rect');

            $rect->setAttribute('width', $animData['w']);
            $rect->setAttribute('height', $animData['h']);
            $rect->setAttribute('x', 0);
            $rect->setAttribute('y', 0);

            $maskId = createElementID();

            $maskElement->setAttribute('id', $maskId);
            $maskElement->appendChild($rect);

            $locationHref = ''; // getLocationHref();

            $this->layerElement?->setAttribute(
                'clip-path',
                "url({$locationHref}#{$maskId})"
            );

            $defs->appendChild($maskElement);
            $this->layers = $animData['layers'];
            $this->elements = createSizedArray(count($animData['layers']));
        } catch (\Throwable $error) {
            echo get_class($this).":\n".$error->getMessage()."\n".$error->getTraceAsString()."\n";
        }
    }

    public function createImage(array $data): ImageElement
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).": Can't access globalData");
        }

        return new ImageElement(
            $data,
            $this->globalData,
            $this
        );
    }

    public function createNull(array $data): NullElement
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).": Can't access globalData");
        }

        return new NullElement(
            $data,
            $this->globalData,
            $this
        );
    }

    public function createShape(array $data): SVGShapeElement
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).": Can't access globalData");
        }

        return new SVGShapeElement(
            $data,
            $this->globalData,
            $this
        );
    }

    public function createSolid(array $data): SolidElement
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).": Can't access globalData");
        }

        return new SolidElement(
            $data,
            $this->globalData,
            $this
        );
    }

    public function createText(array $data): SVGTextLottieElement
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).": Can't access globalData");
        }

        return new SVGTextLottieElement(
            $data,
            $this->globalData,
            $this
        );
    }

    public function findIndexByInd(?int $ind)
    {
        $length = count($this->layers);

        for ($i = 0; $i < $length; ++$i) {
            if ($this->layers[$i]['ind'] === $ind) {
                return $i;
            }
        }

        return -1;
    }

    public function updateContainerSize(?int $_width, ?int $_height)
    {
        throw new \Error(get_class($this).': Method updateContainerSize is not implemented');
    }
}
abstract class SVGRendererBase extends BaseRenderer
{
    use SVGRendererTraits;

    public $destroyed = false;
    public array $renderConfig = [];
    public \DOMElement $svgElement;

    public function destroy()
    {
        if (!$this->animationItem) {
            throw new \Error(get_class($this).": Can't access animationItem");
        }
        if (!$this->globalData) {
            throw new \Error(get_class($this).": Can't access globalData");
        }

        if ($this->animationItem->wrapper) {
            $this->animationItem->wrapper->textContent = '';
        }
        $this->layerElement = null;
        $this->globalData['defs'] = null;
        $length = count($this->layers);

        for ($i = 0; $i < $length; ++$i) {
            $this->elements[$i]?->destroy();
        }
        $this->elements = [];
        $this->destroyed = true;
        $this->animationItem = null;
    }

    public function hide()
    {
        if (!$this->layerElement) {
            throw new \Error(get_class($this).': layerElement is not implemented');
        }
        addStyles($this->layerElement, ['display' => 'none']);
    }

    public function renderFrame(?int $numFromProps = null)
    {
        try {
            if (
                !$this->globalData
                || $this->renderedFrame === $numFromProps
                || $this->destroyed
            ) {
                return;
            }
            $num = $numFromProps;

            if (null === $num) {
                $num = $this->renderedFrame;
            } else {
                $this->renderedFrame = (int) $num;
            }
            $this->globalData['frameNum'] = $num;
            ++$this->globalData['frameId'];
            $this->globalData['projectInterface']['currentFrame'] = $num || 0;
            $this->globalData['_mdf'] = false;
            $length = count($this->layers);

            if (!$this->completeLayers) {
                $this->checkLayers($num);
            }
            for ($i = $length - 1; $i >= 0; --$i) {
                if ('boolean' === gettype($this->elements[$i])) {
                    continue;
                }

                if ($this->completeLayers || $this->elements[$i]) {
                    $this->elements[$i]?->prepareFrame((int) $num - ($this->layers[$i]['st'] ?? 0));
                }
            }

            // For still rendering we must draw at least once (frame 0),
            // even if no dynamic properties report _mdf changes.
            if ($this->globalData['_mdf'] || 0 === (int) $num) {
                for ($i = 0; $i < $length; ++$i) {
                    if ('boolean' === gettype($this->elements[$i])) {
                        continue;
                    }

                    if ($this->completeLayers || $this->elements[$i]) {
                        $this->elements[$i]?->renderFrame();
                    }
                }
            }
        } catch (\Throwable $error) {
            echo get_class($this)."\n".$error->getMessage()."\n".$error->getTraceAsString()."\n";
        }
    }

    public function show()
    {
        if (!$this->layerElement) {
            throw new \Error(get_class($this).': layerElement is not implemented');
        }
        addStyles($this->layerElement, ['display' => 'block']);
    }
}
