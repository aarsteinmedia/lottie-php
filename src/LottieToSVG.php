<?php

namespace LottiePHP;

use LottiePHP\Animation\AnimationItem;
use LottiePHP\Animation\AnimationManager;

use function LottiePHP\Utils\DotLottie\getAnimationData;
use function LottiePHP\Utils\isLottie;
use function LottiePHP\Utils\isSafeZip;

class LottieToSVG
{
    public static function clearCache(): void
    {
        self::$loadCache = [];
    }

    public static function isLottie(array $json): bool
    {
        return isLottie($json);
    }

    /**
     * Expose this utility function.
     */
    public static function isSafeZip(\ZipArchive $zip): bool
    {
        return isSafeZip($zip);
    }

    public static function load(
        string $path,
        array $rendererSettings = []
    ): ?AnimationItem {
        RenderBootstrap::ensureInitialized();

        [
            'animations' => $animations,
        ] = getAnimationData($path);

        for ($i = 0; $i < count($animations); ++$i) {
            $animation = $animations[$i];
            if ('string' !== gettype($animation)) {
                continue;
            }

            $animations[$i] = json_decode($animation, true);
        }

        $hasInvalid = false;
        foreach ($animations ?? [] as $animation) {
            if (!isLottie($animation)) {
                $hasInvalid = true;

                break;
            }
        }

        if (!$animations || $hasInvalid) {
            throw new \InvalidArgumentException('Broken or corrupted Lottie file: '.$path);
        }

        $anim = AnimationManager::loadAnimation([
            'animationData' => $animations[0],
        ]);

        $anim->setParams([
            'animationData' => $anim->animationData ?? null,
            'path' => null,
            'assetsPath' => null,
            'rendererSettings' => $rendererSettings,
        ]);

        $anim->renderer->itemsInitialized = false;

        return $anim;
    }

    /**
     * Load an animation, reusing a cached instance when the file path and mtime are unchanged.
     */
    public static function loadCached(string $path): ?AnimationItem
    {
        $resolved = realpath($path) ?: $path;
        $mtime = @filemtime($path) ?: 0;
        $cached = self::$loadCache[$resolved] ?? null;

        if ($cached && $cached['mtime'] === $mtime) {
            return $cached['anim'];
        }

        $anim = self::load($path);
        if ($anim) {
            self::$loadCache[$resolved] = [
                'mtime' => $mtime,
                'anim' => $anim,
            ];
        }

        return $anim;
    }

    public static function renderFrame(
        string $path,
        array $rendererSettings = [],
        ?int $frame = null,
        bool $force = false
    ): ?string {
        $useCache = [] === ($rendererSettings ?? []);

        $anim = $useCache
            ? self::loadCached($path)
            : self::load($path, $rendererSettings);

        if (!$anim?->renderer) {
            return null;
        }

        return self::renderFrameFromAnimation($anim, $frame, $force);
    }

    /**
     * Render one frame from an already-loaded animation (avoids JSON/DOM setup per call).
     */
    public static function renderFrameFromAnimation(
        AnimationItem $anim,
        ?int $frame = null,
        bool $force = false
    ): ?string {
        if (!$anim->renderer) {
            return null;
        }

        $anim->renderer->initItems();
        $anim->renderer->renderFrame($frame, $force);

        return method_exists($anim->renderer, 'toSVGString')
            ? $anim->renderer->toSVGString()
            : null;
    }

    /** @var array<string, array{mtime: int, anim: AnimationItem}> */
    private static array $loadCache = [];
}
