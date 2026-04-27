<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Enums\RendererType;
use LottiePHP\Utils\Helpers\Constants;

class ImagePreloader
{
    public $assetsPath = '';
    public $images = [];
    public ?\Closure $imagesLoadedCb = null;
    public $loadedAssets = 0;
    public $loadedFootagesCount = 0;
    public $path = '';
    public $totalFootages = 0;
    public $totalImages = 0;
    private ?\Closure $_createImageData;
    private ?\DOMElement $_elementHelper;
    // private ?\DOMElement $proxyImage;

    // public function __construct()
    // {
    //     $this->proxyImage = $this->_createProxyImage();
    // }

    public function createFootageData(array $data)
    {
        $obj = [
            'assetData' => $data,
            'img' => null,
        ];
        $path = $this->getAssetsPath(
            $data,
            $this->assetsPath,
            $this->path
        );

        DataManager::loadData(
            $path,
            function ($footageData) {
                if ($footageData) {
                    $obj['img'] = $footageData;
                }
                $this->_footageLoaded();
            },
            function () {
                $this->_footageLoaded();
            }
        );

        return $obj;
    }

    public function createImageData(array $assetData)
    {
        $path = $this->getAssetsPath(
            $assetData,
            $this->assetsPath,
            $this->path
        );
        $img = createNS('image');

        $obj = [
            'assetData' => $assetData,
            'img' => $img,
        ];

        $img->setAttributeNS(
            Constants::$namespaceXlink,
            'href',
            $path
        );

        $this->_elementHelper?->appendChild($img);

        return $obj;
    }

    public function destroy()
    {
        $this->imagesLoadedCb = null;
        $this->images = [];
    }

    public function footageLoaded()
    {
        ++$this->loadedFootagesCount;
        if (
            $this->loadedAssets === $this->totalImages
            && $this->loadedFootagesCount === $this->totalFootages
            && $this->imagesLoadedCb
        ) {
            ($this->imagesLoadedCb)(null);
        }
    }

    public function getAsset(?array $assetData)
    {
        $i = 0;
        $length = count($this->images);

        while ($i < $length) {
            if ($this->images[$i]['assetData'] === $assetData) {
                return $this->images[$i]['img'] ?? null;
            }
            ++$i;
        }

        return null;
    }

    public function imageLoaded()
    {
        ++$this->loadedAssets;
        if (
            $this->loadedAssets === $this->totalImages
            && $this->loadedFootagesCount === $this->totalFootages
            && $this->imagesLoadedCb
        ) {
            ($this->imagesLoadedCb)(null);
        }
    }

    public function loadAssets(
        array $assets,
        \Closure $cb
    ) {
        $this->imagesLoadedCb = $cb;
        $length = count($assets);

        for ($i = 0; $i < $length; ++$i) {
            if ($assets[$i]['layers'] ?? null) {
                continue;
            }
            if ((!$assets[$i]['t'] ?? 'seq' === $assets[$i]['t'] ?? false) && $this->_createImageData) {
                ++$this->totalImages;
                $imageData = ($this->_createImageData)($assets[$i]);

                if ($imageData) {
                    array_push($this->images, $imageData);
                }

                continue;
            }

            if (($assets[$i]['t'] ?? null) === 3) {
                ++$this->totalFootages;
                array_push($this->images, $this->createFootageData($assets[$i]));
            }
        }
    }

    public function loadedFootages()
    {
        return $this->totalFootages === $this->loadedFootagesCount;
    }

    public function loadedImages()
    {
        return $this->totalImages === $this->loadedAssets;
    }

    public function setAssetsPath(?string $path)
    {
        $this->assetsPath = $path ?? '';
    }

    public function setCacheType(RendererType $type, \DOMElement $elementHelper)
    {
        if (RendererType::SVG === $type) {
            $this->_elementHelper = $elementHelper;
            $this->_createImageData = $this->createImageData(...);
        } else {
            $this->_createImageData = $this->createImgData(...);
        }
    }

    public function setPath(?string $path)
    {
        $this->path = $path ?? '';
    }

    private function _footageLoaded(...$args)
    {
        return $this->footageLoaded(...$args);
    }

    // private function _imageLoaded(...$args)
    // {
    //     return $this->imageLoaded(...$args);
    // }

    // private function _createProxyImage()
    // {
    //     $canvas = createTag(RendererType::Canvas);

    //     $canvas->setAttribute('width', '1');
    //     $canvas->setAttribute('height', '1');

    //     // $ctx = $canvas->getContext('2d');

    //     // if ($ctx) {
    //     //     $ctx->fillStyle = 'rgba(0,0,0,0)';
    //     //     $ctx->fillRect(
    //     //         0,
    //     //         0,
    //     //         1,
    //     //         1
    //     //     );
    //     // }

    //     return $canvas;
    // }

    private function createImgData(array $assetData)
    {
        $path = $this->getAssetsPath(
            $assetData,
            $this->assetsPath,
            $this->path
        );
        $img = createTag('img');

        // if (!img) {
        //   throw new Error("${$this->constructor.name}: Could not create image element")
        // }

        $obj = [
            'assetData' => $assetData,
            'img' => $img,
        ];

        $img->setAttribute('crossOrigin', 'anonymous');
        $img->setAttribute('src', $path);

        return $obj;
    }

    private function getAssetsPath(
        array $assetData,
        string $assetsPath,
        string $originalPath
    ): string {
        if ($assetData['e'] ?? null) {
            return $assetData['p'] ?? '';
        }

        if ($assetsPath) {
            $imagePath = $assetData['p'] ?? '';

            if (str_contains($imagePath, 'images/')) {
                $imagePath = explode('/', $imagePath)[1];
            }

            return "{$assetsPath}{$imagePath}";
        }
        $path = $originalPath;

        $path .= $assetData['u'] ?? '';
        $path .= $assetData['p'] ?? '';

        return $path;
    }

    // private function testImageLoaded(\DOMElement $img)
    // {
    //     return true;
    // }
}
