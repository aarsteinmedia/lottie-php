<?php

namespace LottiePHP\Animation;

use LottiePHP\Renderer\SVGRenderer;
use LottiePHP\Utils\DataManager;
use LottiePHP\Utils\ImagePreloader;

use function LottiePHP\Utils\Pooling\domDocumentPool;

class AnimationItem
{
    public array $animationData;
    public array $assets = [];
    public string $assetsPath = '';
    public ImagePreloader $imagePreloader;
    public string $path = '';
    public ?SVGRenderer $renderer = null;
    public ?\DOMElement $wrapper = null;

    public function __construct()
    {
        $this->imagePreloader = new ImagePreloader();
    }

    public function checkLoaded() {}

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

    public function getAssetsPath(array $assetData)
    {
        $path = '';

        if (!$assetData) {
            return $path;
        }

        if ($assetData['e'] ?? null) {
            $path = $assetData['p'] ?? '';
        } elseif ($this->assetsPath) {
            $imagePath = $assetData['p'] ?? null;

            if (
                array_any(['i/', 'images/'], fn ($_path) => str_contains($imagePath, $_path))
            ) {
                $imagePath = explode('/', $imagePath)[1];
            }
            $path = $this->assetsPath.($imagePath ?: '');
        } else {
            $path = $this->path;
        }

        return $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function imagesLoaded()
    {
        $this->trigger('loaded_images');
        $this->checkLoaded();
    }

    public function preloadImages()
    {
        $this->imagePreloader->setAssetsPath($this->assetsPath);
        $this->imagePreloader->setPath($this->path);
        $this->imagePreloader->loadAssets(
            $this->animationData['assets'],
            $this->imagesLoaded(...)
        );
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

    public function trigger(string $name) {}

    protected ?string $fileName = null;
}
