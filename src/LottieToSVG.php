<?php

namespace LottiePHP;

use LottiePHP\Animation\AnimationItem;
use LottiePHP\Animation\AnimationManager;
use LottiePHP\Utils\BezierFactory;

use function LottiePHP\Utils\DotLottie\getAnimationData;
use function LottiePHP\Utils\isLottie;
use function LottiePHP\Utils\isSafeZip;

class LottieToSVG
{
    private static function trimCache(int $size): void
    {
        while (count(self::$loadCache) > max(0, $size)) {
            unset(self::$loadCache[array_key_first(self::$loadCache)]);
        }
    }

    public static function clearCache(): void
    {
        self::$loadCache = [];
        BezierFactory::clearCache();
        gc_collect_cycles();
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

        // A released animation is a web of reference cycles (renderer <-> elements), which only the
        // cycle collector frees. It runs by root count, not memory pressure, so a few large
        // animations can hit memory_limit first. Collect before allocating the next one.
        gc_collect_cycles();

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

        return AnimationManager::loadAnimation([
            'animationData' => $animations[0],
            'path' => null,
            'assetsPath' => null,
            'rendererSettings' => $rendererSettings,
        ]);
    }

    /**
     * Load an animation, reusing a cached instance while the file and renderer settings are unchanged.
     *
     * The cache keeps at most `setCacheSize()` animations and evicts the least recently used one.
     */
    public static function loadCached(
        string $path,
        array $rendererSettings = []
    ): ?AnimationItem {
        if (0 === self::$cacheSize) {
            return self::load($path, $rendererSettings);
        }

        $resolved = realpath($path) ?: $path;
        // Long-running workers would otherwise keep seeing PHP's cached stat result.
        clearstatcache(true, $resolved);
        $stat = @stat($resolved);
        $version = $stat ? $stat['mtime'].':'.$stat['size'] : '';
        $key = $resolved.'|'.serialize($rendererSettings);
        $cached = self::$loadCache[$key] ?? null;
        unset(self::$loadCache[$key]);

        if ($cached && $cached['version'] === $version) {
            // Re-insert at the end, marking it as most recently used.
            self::$loadCache[$key] = $cached;

            return $cached['anim'];
        }

        // Free room (and any stale entry) before parsing, so the old and new animations never coexist.
        $cached = null;
        self::trimCache(self::$cacheSize - 1);

        $anim = self::load($path, $rendererSettings);
        if ($anim) {
            self::$loadCache[$key] = [
                'anim' => $anim,
                'version' => $version,
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
        $anim = self::loadCached($path, $rendererSettings);

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

    /**
     * Set how many parsed animations `loadCached()` and `renderFrame()` keep in memory. 0 disables caching.
     */
    public static function setCacheSize(int $size): void
    {
        if ($size < 0) {
            throw new \InvalidArgumentException('Cache size must be 0 or greater');
        }

        self::$cacheSize = $size;
        self::trimCache($size);
    }

    private static int $cacheSize = 1;

    /** @var array<string, array{anim: AnimationItem, version: string}> */
    private static array $loadCache = [];
}
