<?php

namespace LottiePHP;

require dirname(__DIR__, 1).'/vendor/autoload.php';

use LottiePHP\Animation\AnimationManager;

use function LottiePHP\Utils\DotLottie\getAnimationData;
use function LottiePHP\Utils\isLottie;

class LottieToSVG
{
    public static function load(string $path)
    {
        try {
            // Load the resource
            [
                'animations' => $animations,
            ] = getAnimationData($path);

            // Convert all animations to associative array
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
                throw new \Error('Broken or corrupted file');
            }

            // Initialize lottie player and load animation
            return AnimationManager::loadAnimation([
                'animationData' => $animations[0],
            ]);
        } catch (\Throwable $error) {
            echo $error::class.': '.$error->getMessage()."\n".$error->getTraceAsString()."\n";
        }
    }

    public static function loadAnimation(array $params)
    {
        return AnimationManager::loadAnimation($params);
    }

    public static function renderFirstFrame(string $path, ?array $rendererSettings = []): ?string
    {
        $anim = self::load($path);
        if (!$anim || !$anim->renderer) {
            return null;
        }

        // Ensure renderer settings are applied (optional)
        if ($rendererSettings) {
            $anim->setParams([
                'animationData' => $anim->animationData ?? null,
                'path' => null,
                'assetsPath' => null,
                'rendererSettings' => $rendererSettings,
            ]);
        }

        $anim->renderer->initItems();
        $anim->renderer->renderFrame(0);

        return method_exists($anim->renderer, 'toSVGString')
            ? $anim->renderer->toSVGString()
            : null;
    }
}
