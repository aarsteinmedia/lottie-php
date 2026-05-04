<?php

namespace LottiePHP;

require dirname(__DIR__, 1).'/vendor/autoload.php';

use LottiePHP\Animation\AnimationManager;
use LottiePHP\Effects\Svg\SVGDropShadowEffect;
use LottiePHP\Effects\Svg\SVGFillFilter;
use LottiePHP\Effects\Svg\SVGGaussianBlurEffect;
use LottiePHP\Effects\Svg\SVGMatte3Effect;
use LottiePHP\Effects\Svg\SVGProLevelsFilter;
use LottiePHP\Effects\Svg\SVGStrokeEffect;
use LottiePHP\Effects\Svg\SVGTintFilter;
use LottiePHP\Effects\Svg\SVGTransformEffect;
use LottiePHP\Effects\Svg\SVGTritoneFilter;
use LottiePHP\Elements\SVGEffects;
use LottiePHP\Utils\Enums\Modifier;
use LottiePHP\Utils\Shapes\Modifiers\OffsetPathModifier;
use LottiePHP\Utils\Shapes\Modifiers\PuckerAndBloatModifier;
use LottiePHP\Utils\Shapes\Modifiers\RepeaterModifier;
use LottiePHP\Utils\Shapes\Modifiers\RoundCornersModifier;
use LottiePHP\Utils\Shapes\Modifiers\ShapeModifiers;
use LottiePHP\Utils\Shapes\Modifiers\TrimModifier;
use LottiePHP\Utils\Shapes\Modifiers\ZigZagModifier;

use function LottiePHP\Utils\DotLottie\getAnimationData;
use function LottiePHP\Utils\isLottie;

class LottieToSVG
{
    public static function load(string $path)
    {
        try {
            ShapeModifiers::registerModifier(Modifier::TrimModifier, TrimModifier::class);
            ShapeModifiers::registerModifier(Modifier::PuckerAndBloatModifier, PuckerAndBloatModifier::class);
            ShapeModifiers::registerModifier(Modifier::RepeaterModifier, RepeaterModifier::class);
            ShapeModifiers::registerModifier(Modifier::RoundCornersModifier, RoundCornersModifier::class);
            ShapeModifiers::registerModifier(Modifier::ZigZagModifier, ZigZagModifier::class);
            ShapeModifiers::registerModifier(Modifier::OffsetPathModifier, OffsetPathModifier::class);

            SVGEffects::registerEffect(
                20,
                SVGTintFilter::class,
                true
            );
            SVGEffects::registerEffect(
                21,
                SVGFillFilter::class,
                true
            );
            SVGEffects::registerEffect(
                22,
                SVGStrokeEffect::class,
                false
            );
            SVGEffects::registerEffect(
                23,
                SVGTritoneFilter::class,
                true
            );
            SVGEffects::registerEffect(
                24,
                SVGProLevelsFilter::class,
                true
            );
            SVGEffects::registerEffect(
                25,
                SVGDropShadowEffect::class,
                true
            );
            SVGEffects::registerEffect(
                28,
                SVGMatte3Effect::class,
                false
            );
            SVGEffects::registerEffect(
                29,
                SVGGaussianBlurEffect::class,
                true
            );
            SVGEffects::registerEffect(
                35,
                SVGTransformEffect::class,
                false
            );

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

    public static function renderFrame(string $path, ?array $rendererSettings = [], ?int $frame = 0): ?string
    {
        $anim = self::load($path);
        if (!$anim?->renderer) {
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
        $anim->renderer->renderFrame($frame);

        return method_exists($anim->renderer, 'toSVGString')
            ? $anim->renderer->toSVGString()
            : null;
    }
}
