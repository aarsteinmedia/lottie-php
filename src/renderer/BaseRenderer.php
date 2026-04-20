<?php

namespace LottiePHP\Renderer;

use LottiePHP\Animation\AnimationItem;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Elements\FootageElement;
use LottiePHP\Elements\Helpers\FrameElement;
use LottiePHP\Elements\ImageElement;
use LottiePHP\Elements\NullElement;
use LottiePHP\Elements\SolidElement;
use LottiePHP\Elements\SVGCompElement;
use LottiePHP\Elements\SVGShapeElement;
use LottiePHP\Elements\SVGTextLottieElement;
use LottiePHP\Utils\FontManager;

trait RendererTraits
{
    public function buildAllItems()
    {
        $length = count($this->layers);

        for ($i = 0; $i < $length; ++$i) {
            $this->buildItem($i);
        }
        $this->checkPendingElements();
    }

    public function buildElementParenting(
        array $element,
        ?int $parentName,
        array $hierarchy = []
    ) {
        $length = count($this->layers);
        $i = 0;

        while ($i < $length) {
            if ($this->layers[$i]['ind'] !== $parentName) {
                ++$i;

                continue;
            }

            $el = $this->elements[$i];

            if (
                !$el
                || true === $el
            ) {
                $this->buildItem($i);

                $this->addPendingElement($element);
                ++$i;

                continue;
            }
            array_push($hierarchy, $el);
            $el->setAsParent();

            if (null === $this->layers[$i]['parent']) {
                $element['setHierarchy']($hierarchy);
                ++$i;

                continue;
            }
            $this->buildElementParenting(
                $element,
                $this->layers[$i]['parent'],
                $hierarchy
            );
            ++$i;
        }
    }

    public function checkLayers(?int $val)
    {
        $this->completeLayers = true;
        $length = count($this->layers);

        for ($i = $length - 1; $i >= 0; --$i) {
            $layer = $this->layers[$i];

            if (!$this->elements[$i] && $layer
              && ($layer['ip'] - $layer['st']
                <= (int) $val - $layer['st']
                && $layer['op'] - $layer['st']
                > (int) $val - $layer['st'])
            ) {
                $this->buildItem($i);
            }
            $this->completeLayers = $this->elements[$i] ? $this->completeLayers : false;
        }
        $this->checkPendingElements();
    }

    public function createFootage(array $data)
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).": Can't access globalData");
        }

        return new FootageElement(
            $data,
            $this->globalData,
            $this
        );
    }

    public function createItem(array $layer)
    {
        switch ($layer['ty']) {
            case 2:
                return $this->createImage($layer);

            case 0:
                return $this->createComp($layer);

            case 1:
                return $this->createSolid($layer);

            case 3:
                return $this->createNull($layer);

            case 4:
                return $this->createShape($layer);

            case 5:
                return $this->createText($layer);

            case 15:
                return $this->createFootage($layer);

            default:
                return $this->createNull($layer);
        }
    }

    public function getElementById(int $ind)
    {
        $length = count($this->elements);

        for ($i = 0; $i < $length; ++$i) {
            if ($this->elements[$i]->data['ind'] === $ind) {
                return $this->elements[$i] ?? null;
            }
        }

        return null;
    }

    public function getElementByPath(array $path)
    {
        $pathValue = array_shift($path);
        $element = null;

        if ('number' === gettype($pathValue)) {
            $element = $this->elements[$pathValue];
        } else {
            $length = count($this->elements);

            for ($i = 0; $i < $length; ++$i) {
                if ($this->elements[$i]?->data['nm'] === $pathValue) {
                    $element = $this->elements[$i];

                    break;
                }
            }
        }
        if (0 === count($path)) {
            return $element;
        }

        return $element->getElementByPath($path);
    }

    public function includeLayers(array $newLayers)
    {
        $this->completeLayers = false;
        $length = count($newLayers);
        $jLen = count($this->layers);

        for ($i = 0; $i < $length; ++$i) {
            $j = 0;

            while ($j < $jLen) {
                if ($this->layers[$j]['id'] === $newLayers[$i]['id']) {
                    $this->layers[$j] = $newLayers[$i];

                    break;
                }
                ++$j;
            }
        }
    }

    public function initItems()
    {
        if (!$this->globalData['progressiveLoad']) {
            $this->buildAllItems();
        }
    }

    public function searchExtraCompositions(array $assets)
    {
        $length = count($assets);

        for ($i = 0; $i < $length; ++$i) {
            if ($assets[$i]['xt']) {
                $comp = $this->createComp($assets[$i]);

                // $comp->initExpressions();
                $this->globalData['projectInterface']['registerComposition']($comp);
            }
        }
    }

    public function setupGlobalData(
        array $animData,
        \DOMElement $fontsContainer
    ) {
        if (!$this->globalData) {
            throw new \Error(get_class($this).': globalData is not implemented');
        }
        if (!$this->animationItem) {
            throw new \Error(get_class($this).': animationItem is not implemented');
        }
        $this->globalData['fontManager'] = new FontManager();
        // Slot manager is used for runtime text/image replacement in players.
        // For a still frame renderer we can omit it safely.
        $this->globalData['slotManager'] = null;
        $this->globalData['fontManager']->addChars($animData['chars'] ?? []);
        $this->globalData['fontManager']->addFonts($animData['fonts'] ?? [], $fontsContainer);

        // Not needed for still SVG output (keep null-safe to avoid crashes).
        $this->globalData['imageLoader'] = null;
        $this->globalData['audioController'] = null;
        $this->globalData['frameId'] = 0;
        $this->globalData['frameRate'] = (int) ($animData['fr'] ?? 60);
        $this->globalData['nm'] = $animData['nm'] ?? '';
        $this->globalData['compSize'] = [
            'h' => $animData['h'],
            'w' => $animData['w'],
        ];
    }
}

abstract class BaseRenderer extends FrameElement
{
    use RendererTraits;

    public ?AnimationItem $animationItem;
    public $completeLayers = false;
    public $currentFrame = 0;
    public $elements = [];
    public $layers = [];
    public $pendingElements = [];
    public $renderedFrame = -1;

    public function addPendingElement(BaseElement $element)
    {
        array_push($this->pendingElements, $element);
    }

    public function buildItem(int $_val)
    {
        throw new \Error(get_class($this).': Method buildItem not yet implemented');
    }

    public function checkPendingElements()
    {
        throw new \Error(get_class($this).': Method checkPendingElements not yet implemented');
    }

    public function createComp(
        array $_data,
        ?\DOMElement $_container = null,
        ?array $_comp = null,
        $_ = null
    ): SVGCompElement {
        throw new \Error(get_class($this).': Method createComp not yet implemented');
    }

    public function createImage(array $_layer): ImageElement
    {
        throw new \Error(get_class($this).': Method createImage is not implemented');
    }

    public function createNull(array $_layer): NullElement
    {
        throw new \Error(get_class($this).': Method createNull not implemented');
    }

    public function createShape(array $_layer): SVGShapeElement
    {
        throw new \Error(get_class($this).': Method createShape not implemented');
    }

    public function createSolid(array $_layer): SolidElement
    {
        throw new \Error(get_class($this).': Method createSolid not implemented');
    }

    public function createText(array $_layer): SVGTextLottieElement
    {
        throw new \Error(get_class($this).': Method createText not implemented');
    }

    public function prepareFrame(int $_num)
    {
        throw new \Error(get_class($this).': Method prepareFrame not yet implemented');
    }

    public function setProjectInterface(?array $pInterface)
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).': globalData is not implemented');
        }
        if (!$pInterface) {
            return;
        }
        $this->globalData['projectInterface'] = $pInterface;
    }
}
