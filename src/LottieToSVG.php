<?php

namespace LottiePHP;

use LottiePHP\Animation\AnimationItem;
use LottiePHP\Animation\AnimationManager;

use function LottiePHP\Utils\DotLottie\getAnimationData;
use function LottiePHP\Utils\isLottie;

class LottieToSVG
{
    public static function clearCache(): void
    {
        self::$loadCache = [];
    }

    public static function load(string $path): ?AnimationItem
    {
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

        if (
            !$animations
            || array_any($animations, fn ($animation) => !isLottie($animation))
        ) {
            throw new \InvalidArgumentException('Broken or corrupted Lottie file: '.$path);
        }

        return AnimationManager::loadAnimation([
            'animationData' => $animations[0],
        ]);
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
        ?array $rendererSettings = [],
        ?int $frame = null,
        bool $force = false
    ): ?string {
        $useCache = [] === ($rendererSettings ?? []);

        $anim = $useCache
            ? self::loadCached($path)
            : self::load($path);

        if (!$anim?->renderer) {
            return null;
        }

        if (!$useCache && $rendererSettings) {
            $anim->setParams([
                'animationData' => $anim->animationData ?? null,
                'path' => null,
                'assetsPath' => null,
                'rendererSettings' => $rendererSettings,
            ]);
            $anim->renderer->itemsInitialized = false;
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
