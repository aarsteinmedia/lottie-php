<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableDOMElement;
use LottiePHP\Elements\Helpers\Shapes\ProcessedElement;
use LottiePHP\Elements\Helpers\Shapes\SVGShapeData;

abstract class ShapeElement extends RenderableDOMElement
{
    public ?int $_length;
    public array $processedElements = [];
    public array $shapeModifiers = [];
    public array $shapes = [];

    private function processedKey(array $elem): string
    {
        // Many shape items have a stable `ix` index. Use it to avoid collisions between
        // different items that happen to have identical array contents.
        $ty = (string) ($elem['ty'] ?? '');
        if (isset($elem['ix']) && (is_int($elem['ix']) || is_string($elem['ix']))) {
            return "{$ty}:ix:{$elem['ix']}";
        }
        if (isset($elem['nm']) && is_string($elem['nm']) && '' !== $elem['nm']) {
            return "{$ty}:nm:{$elem['nm']}";
        }

        // Fallback: JSON-encode (can collide, but better than strict array equality).
        return "{$ty}:json:".md5(json_encode($elem));
    }

    public function addProcessedElement(array $elem, int $pos)
    {
        $key = $this->processedKey($elem);
        $i = count($this->processedElements);

        while ($i) {
            --$i;
            $processedElement = $this->processedElements[$i];

            if ($processedElement->key === $key) {
                $processedElement->pos = $pos;

                return;
            }
        }
        array_push($this->processedElements, new ProcessedElement($key, $pos));
    }

    public function addShapeToModifiers(array|SVGShapeData $data)
    {
        $length = count($this->shapeModifiers);

        for ($i = 0; $i < $length; ++$i) {
            $this->shapeModifiers[$i]->addShape($data);
        }
    }

    public function isShapeInAnimatedModifiers(array $data)
    {
        $i = 0;
        $length = count($this->shapeModifiers);

        while ($i < $length) {
            if ($this->shapeModifiers[$i]->isAnimatedWithShape($data)) {
                return true;
            }
            ++$i;
        }

        return false;
    }

    public function prepareFrame(float $num)
    {
        $this->prepareRenderableFrame($num);
        $this->prepareProperties($num, $this->isInRange);
    }

    public function renderModifiers()
    {
        if (0 === count($this->shapeModifiers)) {
            return;
        }
        $length = count($this->shapes);

        for ($i = 0; $i < $length; ++$i) {
            $shape = $this->shapes[$i] ?? null;
            if (is_array($shape)) {
                ($shape['sh'] ?? null)?->reset();
            } elseif ($shape instanceof SVGShapeData) {
                $shape->sh?->reset();
            }
        }

        $len = count($this->shapeModifiers);
        $shouldBreakProcess = false;

        for ($i = $len - 1; $i >= 0; --$i) {
            $shouldBreakProcess = $this->shapeModifiers[$i]->processShapes((bool) $this->_isFirstFrame);
            // workaround to fix cases where a repeater resets the shape so the following processes get called twice
            // TODO: find a better solution for $this
            if ($shouldBreakProcess) {
                break;
            }
        }
    }

    public function searchProcessedElement($elem)
    {
        if (!is_array($elem)) {
            return 0;
        }
        $key = $this->processedKey($elem);
        $length = count($this->processedElements);
        $i = 0;

        while ($i < $length) {
            $processedElement = $this->processedElements[$i];
            if ($processedElement->key === $key) {
                return $processedElement->pos;
            }
            ++$i;
        }

        return 0;
    }
}
