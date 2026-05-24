<?php

namespace LottiePHP;

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

/**
 * One-time registration of SVG shape modifiers and layer effects for still-frame rendering.
 */
final class RenderBootstrap
{
    public static function ensureInitialized(): void
    {
        if (self::$initialized) {
            return;
        }

        ShapeModifiers::registerModifier(Modifier::TrimModifier, TrimModifier::class);
        ShapeModifiers::registerModifier(Modifier::PuckerAndBloatModifier, PuckerAndBloatModifier::class);
        ShapeModifiers::registerModifier(Modifier::RepeaterModifier, RepeaterModifier::class);
        ShapeModifiers::registerModifier(Modifier::RoundCornersModifier, RoundCornersModifier::class);
        ShapeModifiers::registerModifier(Modifier::ZigZagModifier, ZigZagModifier::class);
        ShapeModifiers::registerModifier(Modifier::OffsetPathModifier, OffsetPathModifier::class);

        SVGEffects::registerEffect(20, SVGTintFilter::class, true);
        SVGEffects::registerEffect(21, SVGFillFilter::class, true);
        SVGEffects::registerEffect(22, SVGStrokeEffect::class, false);
        SVGEffects::registerEffect(23, SVGTritoneFilter::class, true);
        SVGEffects::registerEffect(24, SVGProLevelsFilter::class, true);
        SVGEffects::registerEffect(25, SVGDropShadowEffect::class, true);
        SVGEffects::registerEffect(28, SVGMatte3Effect::class, false);
        SVGEffects::registerEffect(29, SVGGaussianBlurEffect::class, true);
        SVGEffects::registerEffect(35, SVGTransformEffect::class, false);

        self::$initialized = true;
    }
    private static bool $initialized = false;
}
