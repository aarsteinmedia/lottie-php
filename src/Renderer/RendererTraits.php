<?php

namespace LottiePHP\Renderer;

use LottiePHP\Animation\AnimationItem;
use LottiePHP\Elements\BaseElement;
use LottiePHP\Elements\FootageElement;
use LottiePHP\Utils\FontManager;
use LottiePHP\Utils\Properties\BaseProperty;

/** @mixin BaseRenderer */
trait RendererTraits
{
    public ?AnimationItem $animationItem;
    public float $currentFrame = 0;
    public array $elements = [];
    public bool $itemsInitialized = false;
    public array $layers = [];
    public array $pendingElements = [];

    public function addPendingElement(BaseElement $element)
    {
        array_push($this->pendingElements, $element);
    }

    public function buildAllItems()
    {
        $length = count($this->layers);

        for ($i = 0; $i < $length; ++$i) {
            $this->buildItem($i);
        }
        $this->checkPendingElements();
    }

    public function buildElementParenting(
        BaseElement|BaseProperty $element,
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

            if (null === ($this->layers[$i]['parent'] ?? null)) {
                // In JS this is a function reference; in PHP call the method.
                $element->setHierarchy($hierarchy);
                ++$i;

                continue;
            }
            $this->buildElementParenting(
                $element,
                $this->layers[$i]['parent'] ?? null,
                $hierarchy
            );
            ++$i;
        }
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

        if (is_numeric($pathValue)) {
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

    public function initItems()
    {
        if ($this->itemsInitialized) {
            return;
        }

        $this->buildAllItems();
        $this->itemsInitialized = true;
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
        $this->globalData['fontManager']->addChars($animData['chars'] ?? []);
        $this->globalData['fontManager']->addFonts($animData['fonts'] ?? [], $fontsContainer);

        $this->globalData['getAssetData'] = $this->animationItem->getAssetData(...);
        $this->globalData['getAssetsPath'] = $this->animationItem->getAssetsPath(...);

        $this->globalData['imageLoader'] = null;
        $this->globalData['audioController'] = null;
        $this->globalData['frameId'] = 0;
        $this->globalData['frameRate'] = (float) ($animData['fr'] ?? 60);
        $this->globalData['nm'] = $animData['nm'] ?? '';
        $this->globalData['compSize'] = [
            'h' => $animData['h'],
            'w' => $animData['w'],
        ];
    }
}
