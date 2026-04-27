<?php

namespace LottiePHP\Animation;

use LottiePHP\Renderer\SVGRenderer;
use LottiePHP\Utils\DataManager;

use function LottiePHP\Utils\Pooling\domDocumentPool;

class AnimationItem
{
    public array $animationData;
    public $assets = [];
    public ?SVGRenderer $renderer = null;
    public $assetsPath = '';
    public $path = '';
    public ?\DOMElement $wrapper = null;

    protected ?string $fileName = null;

    public function configAnimation(array $animationData)
    {
        try {
            $this->animationData = $animationData;
            $this->renderer->configAnimation($animationData);
            $this->assets = $animationData['assets'];
            $this->renderer->searchExtraCompositions($animationData['assets']);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getAssetData(?string $id)
    {
        $i = 0;
        $length = count($this->assets);

        while ($i < $length) {
            if ($id === ($this->assets[$i]['id'] ?? null)) {
                return $this->assets[$i];
            }
            ++$i;
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
            $path = $this->assetsPath.($imagePath || '');
        } else {
            $path = $this->path;
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    public function renderFrame()
    {
        try {
            $this->renderer->renderFrame();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param array{
     *  path: null|string,
     *  assetsPath: null|string,
     *  animationData: null|array
     * } $params
     */
    public function setParams(array $params): void
    {
        try {
            $this->wrapper = domDocumentPool()->createElement('div');
            domDocumentPool()->appendChild($this->wrapper);

            $this->renderer = new SVGRenderer(
                $this,
                $params['rendererSettings'] ?? [],
            );

            if (!empty($params['assetsPath'])) {
                $this->assetsPath = $params['assetsPath'];
            }

            if (!empty($params['animationData'])) {
                ['animationData' => $animationData] = $params;

                $this->setupAnimation($animationData);

                return;
            }

            $path = $params['path'] ?? null;

            if (!$path) {
                return;
            }

            if (str_contains($path, '\\')) {
                $this->path = substr(
                    $path,
                    0,
                    max(0, strrpos($path, '\\') + 1)
                );
            } else {
                $this->path = substr(
                    $path,
                    0,
                    max(0, strrpos($path, '/') + 1)
                );
            }
            $this->fileName = substr(
                $path,
                0,
                max(0, strrpos($path, '/') + 1)
            );
            $this->fileName = substr(
                $this->fileName,
                0,
                max(0, strrpos($this->fileName, '.json'))
            );

            DataManager::loadAnimation(
                $path,
                $this->configAnimation(...)
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function setupAnimation(array $data)
    {
        DataManager::completeAnimation($data, $this->configAnimation(...));
    }
}
