<?php

namespace LottiePHP\Elements\Helpers;

use LottiePHP\Elements\BaseElement;

use function LottiePHP\Utils\addStyles;

trait RenderableElementTraits
{
    public function hide()
    {
        if ($this->hidden || $this->isInRange && !$this->isTransparent) {
            return;
        }
        $elem = $this->baseElement ?? $this->layerElement;

        if ($elem) {
            addStyles($elem, ['display' => 'none']);
        }

        $this->hidden = true;
    }

    public function renderFrame(?int $_val = null)
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        // If it is exported as hidden (data.hd === true) no need to render
        // If it is not visible no need to render
        if (($this->data['hd'] ?? false) || $this->hidden) {
            return;
        }
        $this->renderTransform();
        $this->renderRenderable();
        $this->renderLocalTransform();
        $this->renderElement();
        $this->renderInnerContent();

        if ($this->_isFirstFrame) {
            $this->_isFirstFrame = false;
        }
    }

    public function show()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        if (!$this->isInRange && $this->isTransparent) {
            return;
        }
        if (!($this->data['hd'] ?? false)) {
            $elem = $this->baseElement ?? $this->layerElement;

            if (!$elem) {
                throw new \Error(get_class($this).': Neither baseElement or layerElement is implemented');
            }

            addStyles($elem, ['display' => 'block']);
        }
        $this->hidden = false;
        $this->_isFirstFrame = true;
    }
}

abstract class RenderableDOMElement extends RenderableElement
{
    use RenderableElementTraits;

    public ?\DOMElement $innerElem = null;

    abstract public function createContainerElements();

    public function createContent()
    {
        throw new \Error(get_class(${$this}).': Method createContent is not implemented');
    }

    abstract public function createRenderableComponents();

    abstract public function destroyBaseElement();

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
        $this->initHierarchy();
        $this->initRenderable();
        $this->initRendererElement();
        $this->createContainerElements();
        $this->createRenderableComponents();
        $this->createContent();
        $this->hide();
    }

    abstract public function initRendererElement();

    public function prepareFrame(float $num)
    {
        $this->_mdf = false;
        $this->prepareRenderableFrame($num);
        $this->prepareProperties($num, $this->isInRange);
        $this->checkTransparency();
    }

    abstract public function renderElement();

    abstract public function renderInnerContent();
}
