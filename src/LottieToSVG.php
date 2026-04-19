<?php

namespace LottiePHP;

use LottiePHP\Animation\AnimationManager;

use function LottiePHP\Utils\DotLottie\getAnimationData;
use function LottiePHP\Utils\isLottie;

class LottieToSVG
{
    public static function loadAnimation(array $params)
    {
        return AnimationManager::loadAnimation($params);
    }

    public static function load(string $path)
    {
        try {
            // Load the resource
            [
                'animations' => $animations, 'manifest' => $manifest,
            ] = getAnimationData($path);

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
        } catch (\Exception $error) {
            error_log($error->getMessage());
        }
    }
}
