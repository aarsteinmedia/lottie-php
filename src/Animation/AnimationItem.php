<?php

namespace LottiePHP\Animation;

use LottiePHP\Renderer\SVGRenderer;
use LottiePHP\Utils\DataManager;

use function LottiePHP\Utils\Pooling\domDocumentPool;

class AnimationItem
{
    public array $animationData;
    public array $assets = [];
    public string $assetsPath = '';
    public int $frameRate = 60;
    public string $path = '';
    public ?SVGRenderer $renderer = null;
    public int $totalFrames = 0;
    public ?\DOMElement $wrapper = null;

    public function configAnimation(array $animationData): void
    {
        $this->animationData = $animationData;
        $this->totalFrames = floor(($this->animationData['op'] ?? 1) - ($this->animationData['ip'] ?? 0));
        $animationData['totalFrames'] = $this->totalFrames;
        $this->renderer->configAnimation($animationData);
        $this->assets = $animationData['assets'];
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

            foreach (['i/', 'images/'] as $_path) {
                if (str_contains($imagePath, $_path)) {
                    $imagePath = explode('/', $imagePath)[1];
                }
            }

            $path = $this->assetsPath.($imagePath ?: '');
        } else {
            $path = $this->path;
        }

        return $path;
    }

    public function getDuration(?bool $isFrame = null)
    {
        return $isFrame ? $this->totalFrames : $this->totalFrames / $this->frameRate;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param array{
     *  path: null|string,
     *  assetsPath: null|string,
     *  animationData: null|array,
     *  rendererSettings?: array
     * } $params
     */
    public function setParams(array $params): void
    {
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
    }

    public function setupAnimation(array $data): void
    {
        DataManager::completeAnimation($data, $this->configAnimation(...));
    }

    protected ?string $fileName = null;
}
