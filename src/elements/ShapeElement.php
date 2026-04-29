<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableDOMElement;
use LottiePHP\Elements\Helpers\Shapes\ProcessedElement;
use LottiePHP\Elements\Helpers\Shapes\SVGShapeData;

abstract class ShapeElement extends RenderableDOMElement
{
    public ?int $_length;
    public $processedElements = [];
    public $shapeModifiers = [];
    public $shapes = [];

    public function addProcessedElement(array $elem, int $pos)
    {
        $i = count($this->processedElements);

        while ($i) {
            --$i;
            $processedElement = $this->processedElements[$i];

            if ($processedElement->elem === $elem) {
                $processedElement->pos = $pos;

                return;
            }
        }
        array_push($this->processedElements, new ProcessedElement($elem, $pos));
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
        $length = count($this->processedElements);
        $i = 0;

        while ($i < $length) {
            $processedElement = $this->processedElements[$i];
            if ($processedElement->elem === $elem) {
                return $processedElement->pos;
            }
            ++$i;
        }

        return 0;
    }
}
