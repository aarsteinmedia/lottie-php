<?php

namespace LottiePHP\Elements;

use LottiePHP\Utils\Helpers\GlobalData;

trait CompTrait
{
    abstract public function buildAllItems();

    public function getElements()
    {
        return $this->elements;
    }

    public function initElement(
        array $data,
        GlobalData $globalData,
        BaseElement $comp
    ) {
        $this->initFrame();
        $this->initBaseData(
            $data,
            $globalData,
            $comp
        );
        $this->initTransform();
        $this->initRenderable();
        $this->initHierarchy();
        $this->initRendererElement();
        $this->createContainerElements();
        $this->createRenderableComponents();
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        $this->buildAllItems();
        $this->hide();
    }

    public function prepareFrame(float $val)
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }

        $this->_mdf = false;
        $this->prepareRenderableFrame($val);
        $this->prepareProperties($val, $this->isInRange);
        if (!$this->isInRange && !($this->data['xt'] ?? false)) {
            return;
        }

        if (!$this->tm || ($this->tm->_placeholder ?? false)) {
            $this->renderedFrame = $val / (float) $this->data['sr'];
        } else {
            $timeRemapped = $this->tm->v ?? 0;

            if ($timeRemapped === $this->data['op']) {
                $timeRemapped = $this->data['op'] - 1;
            }
            $this->renderedFrame = $timeRemapped;
        }
        $length = count($this->elements);

        for ($i = $length - 1; $i >= 0; --$i) {
            if (!is_object($this->elements[$i] ?? null)) {
                continue;
            }

            $this->elements[$i]->prepareFrame($this->renderedFrame - ($this->layers[$i]['st'] ?? 0));
            if ($this->elements[$i]->_mdf) {
                $this->_mdf = true;
            }
        }
    }

    public function renderInnerContent()
    {
        $length = count($this->layers);

        for ($i = 0; $i < $length; ++$i) {
            if (!is_object($this->elements[$i] ?? null)) {
                continue;
            }

            $this->elements[$i]->renderFrame();
        }
    }

    public function setElements(array $elems)
    {
        $this->elements = $elems;
    }
}
