<?php

namespace LottiePHP\Elements\Helpers;

use LottiePHP\Elements\ImageElement;

abstract class RenderableElement extends FrameElement
{
    public $hidden = false;
    public $isInRange = false;
    public $isTransparent = false;
    public array $renderableComponents = [];

    public function addRenderableComponent(ImageElement $component)
    {
        if (!in_array($component, $this->renderableComponents)) {
            $this->renderableComponents[] = $component;
        }
    }

    public function checkLayerLimits(int $num)
    {
        if (!$this->data || !$this->globalData) {
            return;
        }
        if (
            $this->data['ip'] - $this->data['st'] <= $num &&
            $this->data['op'] - $this->data['st'] > $num
        ) {
            if (!$this->isInRange) {
                $this->globalData['_mdf'] = true;
                $this->_mdf = true;
                $this->isInRange = true;
                $this->show();
            }

            return;
        }

        if ($this->isInRange) {
            $this->globalData['_mdf'] = true;
            $this->isInRange = false;
            $this->hide();
        }
    }

    public function checkTransparency()
    {
        if (!$this->finalTransform) {
            throw new \Error(get_class($this) . ': finalTransform is not implemented');
        }
        if ((int)($this->finalTransform['mProp']['o']['v']) <= 0) {
            if (
                !$this->isTransparent &&
                $this->globalData['renderConfig']['hideOnTransparent']
            ) {
                $this->isTransparent = true;
                $this->hide();
            }

            return;
        }

        if ($this->isTransparent) {
            $this->isTransparent = false;
            $this->show();
        }
    }

    public function getLayerSize()
    {
        if (!$this->data) {
            throw new \Error(get_class($this) . ': data (LottieLayer) is not implemented');
        }
        if ($this->data['ty'] === 5) {
            return [
              'h' => (int)($this->data['textData']['height']),
              'w' => (int)($this->data['textData']['width']),
            ];
        }

        return [
          'h' => (int)($this->data['height']),
          'w' => (int)($this->data['width'])
        ];
    }

    public function hide()
    {
        // Pass through
    }

    public function initRenderable()
    {
        // layer's visibility related to inpoint and outpoint. Rename isVisible to isInRange
        $this->isInRange = false;
        // layer's display state
        $this->hidden = false;
        // If layer's transparency equals 0, it can be hidden
        $this->isTransparent = false;
        // list of animated components
        $this->renderableComponents = [];
    }

    public function prepareRenderableFrame(int $num, bool $_)
    {
        $this->checkLayerLimits($num);
    }

    public function removeRenderableComponent(ImageElement $component)
    {
        if (in_array($component, $this->renderableComponents)) {
            array_slice($this->renderableComponents, array_search($component, $this->renderableComponents), 1);
        }
    }

    public function renderRenderable()
    {
        $length = count($this->renderableComponents);

        for ($i = 0; $i < $length; $i++) {
            $this->renderableComponents[$i]->renderFrame((int)($this->_isFirstFrame));
        }
        /* $this->maskManager.renderFrame($this->finalTransform.mat);
            $this->renderableEffectsManager.renderFrame($this->_isFirstFrame); */
    }

    public function show()
    {
        throw new \Error(get_class($this) . ': Method show is not implemented');
    }

    public function sourceRectAtTime()
    {
        return [
          'height' => 100,
          'left' => 0,
          'top' => 0,
          'width' => 100,
        ];
    }
}
