<?php

namespace LottiePHP;

use stdClass;

class AnimationItem
{
    public $animationData = new stdClass();
    public $assets = [];
    public $renderer = null;
    public $assetsPath = '';
    public $path = '';

    protected ?string $fileName = null;

    public function configAnimation($animationData)
    {
        try {
            $this->animationData = $animationData;
            // $this->renderer->configAnimation($animationData);
            $this->assets = $animationData->assets;
            // $this->renderer->searchExtraCompositions($animationData->assets);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getAssetData(?string $id)
    {
        $i = 0;
        $length = count($this->assets);

        while ($i < $length) {
            if ($id === $this->assets[$i]?->id) {
                return $this->assets[$i];
            }
            $i++;
        }

        return null;
    }

    public function getAssetsPath($assetData)
    {

        $path = '';

        if (!$assetData) {
            return $path;
        }

        if ($assetData->e) {
            $path = $assetData->p || '';
        } elseif ($this->assetsPath) {
            $imagePath = $assetData->p;

            if (
                array_any(['i/', 'images/'], fn ($_path) => str_contains($imagePath, $_path))
            ) {
                $imagePath = explode('/', $imagePath)[1];
            }
            $path = $this->assetsPath . ($imagePath || '');
        } else {
            $path = $this->path;
        }
    }
}
