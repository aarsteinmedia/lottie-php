<?php

namespace LottiePHP\Elements\Helpers;

use LottiePHP\Elements\ImageElement;
use LottiePHP\Elements\MaskElement;
use LottiePHP\Elements\SVGEffects;

abstract class RenderableElement extends FrameElement
{
    public $hidden = false;
    public $isInRange = false;
    public $isTransparent = false;
    public array $renderableComponents = [];

    public function addRenderableComponent(ImageElement|MaskElement|SVGEffects $component)
    {
        if (!in_array($component, $this->renderableComponents)) {
            $this->renderableComponents[] = $component;
        }
    }

    public function checkLayerLimits(float $num)
    {
        if (!$this->data || !$this->globalData) {
            return;
        }
        // Embedded shape payloads (font glyph outlines from `chars`, mini shape trees, etc.)
        // omit layer timing (`ip` / `op`). Defaults collapse to a zero-length window so the
        // layer never passes the range test below and stays hidden (display:none).
        if (!array_key_exists('ip', $this->data) && !array_key_exists('op', $this->data)) {
            if (!$this->isInRange) {
                $this->globalData['_mdf'] = true;
                $this->_mdf = true;
                $this->isInRange = true;
                $this->show();
            }

            return;
        }
        if (
            ($this->data['ip'] ?? 0) - ($this->data['st'] ?? 0) <= $num
            && ($this->data['op'] ?? 0) - ($this->data['st'] ?? 0) > $num
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
            throw new \Error(get_class($this).': finalTransform is not implemented');
        }
        $mProp = $this->finalTransform['mProp'] ?? null;
        $o = is_object($mProp)
            ? ($mProp->o ?? null)
            : (is_array($mProp) ? ($mProp['o'] ?? null) : null);
        $opacity = 1;
        if (is_object($o)) {
            $opacity = $o->v ?? 1;
        } elseif (is_array($o)) {
            $opacity = $o['v'] ?? 1;
        } elseif (is_int($o) || is_float($o)) {
            $opacity = $o;
        }
        if ((float) $opacity <= 0) {
            if (
                !$this->isTransparent
                && ($this->globalData['renderConfig']['hideOnTransparent'] ?? false)
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
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        if (5 === $this->data['ty']) {
            return [
                'h' => (float) $this->data['textData']['height'],
                'w' => (float) $this->data['textData']['width'],
            ];
        }

        return [
            'h' => (float) $this->data['height'],
            'w' => (float) $this->data['width'],
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

    public function prepareRenderableFrame(float $num, $_ = false)
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

        for ($i = 0; $i < $length; ++$i) {
            $this->renderableComponents[$i]->renderFrame((int) $this->_isFirstFrame);
        }
        /* $this->maskManager.renderFrame($this->finalTransform.mat);
            $this->renderableEffectsManager.renderFrame($this->_isFirstFrame); */
    }

    public function show()
    {
        throw new \Error(get_class($this).': Method show is not implemented');
    }

    public function sourceRectAtTime(): ?array
    {
        return [
            'height' => 100,
            'left' => 0,
            'top' => 0,
            'width' => 100,
        ];
    }
}
