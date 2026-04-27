<?php

namespace LottiePHP\Elements;

trait CompTrait
{
    public function destroy()
    {
        $this->destroyElements();
        $this->destroyBaseElement();
    }

    public function destroyElements()
    {
        $length = count($this->layers);

        for ($i = 0; $i < $length; ++$i) {
            $this->elements[$i]?->destroy();
        }
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function initElement(
        array $data,
        array $globalData,
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
        if ($this->data['xt'] || !$globalData['progressiveLoad']) {
            $this->buildAllItems();
        }
        $this->hide();
    }

    public function prepareFrame(int $val)
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }

        $this->_mdf = false;
        $this->prepareRenderableFrame($val);
        $this->prepareProperties($val, $this->isInRange);
        if (!$this->isInRange && !$this->data['xt']) {
            return;
        }

        if ($this->tm?->_placeholder) {
            $this->renderedFrame = $val / (int) $this->data['sr'];
        } else {
            $timeRemapped = $this->tm?->v || 0;

            if ($timeRemapped === $this->data['op']) {
                $timeRemapped = $this->data['op'] - 1;
            }
            $this->renderedFrame = $timeRemapped;
        }
        $length = count($this->elements);

        if (!$this->completeLayers) {
            // if (!$this->checkLayers) {
            //   throw new Error("${$this->constructor.name}: Method checkLayers is not implemented")
            // }
            $this->checkLayers($this->renderedFrame);
        }
        // This iteration needs to be backwards because of how expressions connect between each other
        for ($i = $length - 1; $i >= 0; --$i) {
            if ($this->completeLayers || $this->elements[$i]) {
                $this->elements[$i]?->prepareFrame($this->renderedFrame - ($this->layers[$i]['st'] ?? 0));
                if ($this->elements[$i]?->_mdf) {
                    $this->_mdf = true;
                }
            }
        }
    }

    public function renderInnerContent()
    {
        $length = count($this->layers);

        for ($i = 0; $i < $length; ++$i) {
            if ($this->completeLayers || $this->elements[$i]) {
                $this->elements[$i]->renderFrame();
            }
        }
    }

    public function setElements(array $elems)
    {
        $this->elements = $elems;
    }
}
