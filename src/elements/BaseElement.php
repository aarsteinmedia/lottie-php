<?php

namespace LottiePHP\Elements;

use LottiePHP\Effects\EffectsManager;

use function LottiePHP\Utils\addStyles;
use function LottiePHP\Utils\createElementID;
use function LottiePHP\Utils\getBlendMode;

abstract class BaseElement implements \ArrayAccess
{
    public ?\DOMElement $baseElement = null;
    public ?BaseElement $comp = null;
    public ?array $compInterface;
    public ?array $data;
    public ?array $dynamicProperties;
    public ?EffectsManager $effectsManager;
    public ?array $finalTransform;
    public $frameDuration = 1;
    public ?array $globalData;
    public ?array $itemsData = [];
    public ?\DOMElement $layerElement = null;
    public ?string $layerId;
    public ?array $layerInterface = null;
    public ?MaskElement $maskManager;
    public $renderedFrame = -1;
    public ?array $shapesData = [];
    public $type;

    public function addRenderableComponent(ImageElement|MaskElement|SVGEffects $component)
    {
        throw new \Error(get_class($this).': Method addRenderableComponent is not implemented');
    }

    public function buildAllItems()
    {
        throw new \Error(get_class($this).': Method buildAllItems is not implemented');
    }

    public function checkLayers(?int $_frame)
    {
        throw new \Error(get_class($this).': Method checkLayers is not implemented');
    }

    public function checkMasks()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not initialized');
        }
        if (!($this->data['hasMask'] ?? false)) {
            return false;
        }
        $i = 0;
        $length = count($this->data['masksProperties'] ?? []);

        while ($i < $length) {
            if (
                'n' !== $this->data['masksProperties'][$i]['mode']
                && false !== ($this->data['masksProperties'][$i]['cl'] ?? null)
            ) {
                return true;
            }
            ++$i;
        }

        return false;
    }

    public function destroy()
    {
        /*
         * If method is not implemented, it probably shouldn't throw an error
         * Pass through.
         */
    }

    public function destroyBaseElement()
    {
        throw new \Error(get_class($this).': Method destroyBaseElement is not implemented');
    }

    public function getBaseElement(): ?\DOMElement
    {
        throw new \Error(get_class($this).': Method getBaseElement is not implemented');
    }

    public function getType()
    {
        return $this->type;
    }

    public function initBaseData(
        array $data,
        array $globalData,
        BaseElement $comp
    ) {
        $this->globalData = $globalData;
        $this->comp = $comp;
        $this->data = $data;
        $this->layerId = createElementID();

        // Stretch factor for old animations missing $this property.
        if (!$this->data['sr']) {
            $this->data['sr'] = 1;
        }
        $this->effectsManager = new EffectsManager(
            $this->data,
            $this,
            $this->dynamicProperties
        );
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset) || !property_exists($this, $offset)) {
            return null;
        }

        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            return;
        }
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        if (!is_string($offset) || !property_exists($this, $offset)) {
            return;
        }
        $this->{$offset} = null;
    }

    public function setBlendMode()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        $blendModeValue = getBlendMode($this->data['bm'] ?? 16);
        $elem = $this->baseElement ?? $this->layerElement;

        if (!$elem) {
            throw new \Error(get_class($this).': Both baseElement and layerElement are not implemented');
        }
        addStyles(
            $elem,
            ['mix-blend-mode' => $blendModeValue]
        );
    }

    public function sourceRectAtTime()
    {
        throw new \Error(get_class($this).': Method sourceRectAtTime is not implemented');
    }
}
