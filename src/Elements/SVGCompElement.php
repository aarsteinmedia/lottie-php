<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableElementTraits;
use LottiePHP\Renderer\BaseRenderer;
use LottiePHP\Renderer\RendererTraits;
use LottiePHP\Renderer\SVGRendererTraits;
use LottiePHP\Utils\Properties\KeyframeValueProperty;
use LottiePHP\Utils\PropertyFactory;

use function LottiePHP\Utils\createSizedArray;

class SVGCompElement extends SVGBaseElement
{
    use CompTrait;
    use RenderableElementTraits;
    use RendererTraits;
    use SVGRendererTraits;

    public ?bool $_debug;
    public bool $supports3d = true;
    public ?KeyframeValueProperty $tm;

    public function __construct(
        array $data,
        array $globalData,
        BaseRenderer|SVGCompElement|SVGTextLottieElement $comp
    ) {
        $this->layers = $data['layers'] ?? [];
        $this->elements = createSizedArray(count($this->layers));

        $this->initElement(
            $data,
            $globalData,
            $comp
        );
        $this->tm = !empty($data['tm'])
            ? PropertyFactory::getProp(
                $this,
                $data['tm'],
                0,
                $globalData['frameRate'],
            )
            : null;
    }

    public function createComp(array $data): SVGCompElement
    {
        if (!$this->globalData) {
            throw new \Error(get_class($this).': Cannot access global data');
        }

        // Pre-comp layers (`ty: 0`) reference their actual layer stack via `refId`.
        // Those compositions live under `assets[]`. If we don't resolve them here,
        // nested pre-comps will render as empty groups (and any mattes inside them
        // will never be built).
        if (
            isset($data['refId'])
            && is_string($data['refId'])
            && isset($this->globalData['getAssetData'])
            && is_callable($this->globalData['getAssetData'])
        ) {
            /** @var ?array $asset */
            $asset = ($this->globalData['getAssetData'])($data['refId']);
            if (is_array($asset)) {
                if (!isset($data['layers']) && isset($asset['layers']) && is_array($asset['layers'])) {
                    $data['layers'] = $asset['layers'];
                }
                // Pre-comp dimensions are defined on the asset, not always on the layer.
                if (!isset($data['w']) && isset($asset['w'])) {
                    $data['w'] = $asset['w'];
                }
                if (!isset($data['h']) && isset($asset['h'])) {
                    $data['h'] = $asset['h'];
                }
            }
        }

        return new SVGCompElement(
            $data,
            $this->globalData,
            $this
        );
    }
}
