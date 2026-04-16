<?php

namespace LottiePHP\Elements;

use DOMElement;
use LottiePHP\Effects\EffectsManager;

use function LottiePHP\Utils\addStyles;
use function LottiePHP\Utils\createElementID;
use function LottiePHP\Utils\getBlendMode;

abstract class BaseElement
{
    public null|DOMElement $baseElement = null;
    public ?CompElement $comp;
    public ?array $compInterface;
    public ?array $data;
    public ?array $dynamicProperties;
    public ?EffectsManager $effectsManager;
    public $frameDuration = 1;
    public ?array $globalData;
    public array $itemsData = [];
    public null|DOMElement $layerElement = null;
    public ?string $layerId;
    public ?array $layerInterface = null;
    public ?MaskElement $maskManager;
    public ?array $shapesData = [];
    public $type;

    public function buildAllItems()
    {
        throw new \Error(get_class($this) . ": Method buildAllItems is not implemented");
    }

    public function checkLayers(?int $_frame)
    {
        throw new \Error(get_class($this) . ": Method checkLayers is not implemented");
    }

    public function checkMasks()
    {
        if (!$this->data) {
            throw new \Error(get_class($this) . ": data (LottieLayer) is not initialized");
        }
        if (!$this->data['hasMask']) {
            return false;
        }
        $i = 0;
        $length = count($this->data['masksProperties'] ?? []);

        while ($i < $length) {
            if (
                $this->data.['masksProperties'][$i]['mode'] !== 'n' &&
                $this->data.['masksProperties'][$i]['cl'] !== false
            ) {
                return true;
            }
            $i++;
        }

        return false;
    }

    public function destroy()
    {
        /**
         * If method is not implemented, it probably shouldn't throw an error
         * Pass through.
         */
    }

    public function destroyBaseElement()
    {
        throw new \Error(get_class($this) . ": Method destroyBaseElement is not implemented");
    }

    public function getBaseElement(): DOMElement|null
    {
        throw new \Error(get_class($this) . ": Method getBaseElement is not implemented");
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

    public function setBlendMode()
    {
        if (!$this->data) {
            throw new \Error(get_class($this) . ": data (LottieLayer) is not implemented");
        }
        $blendModeValue = getBlendMode($this->data['bm']);
        $elem = $this->baseElement ?? $this->layerElement;

        if (!$elem) {
            throw new \Error(get_class($this) . ": Both baseElement and layerElement are not implemented");
        }
        addStyles(
            $elem,
            ['mix-blend-mode' => $blendModeValue]
        );
    }

    public function sourceRectAtTime()
    {
        throw new \Error(get_class($this) . ": Method sourceRectAtTime is not implemented");
    }
}
