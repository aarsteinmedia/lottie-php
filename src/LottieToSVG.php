<?php

namespace LottiePHP;

require dirname(__DIR__, 1).'/vendor/autoload.php';

use LottiePHP\Animation\AnimationItem;
use LottiePHP\Animation\AnimationManager;

use function LottiePHP\Utils\DotLottie\getAnimationData;
use function LottiePHP\Utils\isLottie;

class LottieToSVG
{
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

    public static function renderFrame(string $path, ?array $rendererSettings = [], ?int $frame = null): ?string
    {
        $anim = self::load($path);
        if (!$anim?->renderer) {
            return null;
        }

        if ($rendererSettings) {
            $anim->setParams([
                'animationData' => $anim->animationData ?? null,
                'path' => null,
                'assetsPath' => null,
                'rendererSettings' => $rendererSettings,
            ]);
        }

        $anim->renderer->initItems();
        $anim->renderer->renderFrame($frame);

        return method_exists($anim->renderer, 'toSVGString')
            ? $anim->renderer->toSVGString()
            : null;
    }
}
