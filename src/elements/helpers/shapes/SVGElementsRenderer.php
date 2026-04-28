<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Utils\Enums\ShapeType;
use LottiePHP\Utils\Matrix;

use function LottiePHP\Utils\buildShapeString;
use function LottiePHP\Utils\toRGBString;

class SVGElementsRenderer
{
    private static function renderContentTransform(
        array $_,
        ?SVGTransformData $itemData,
        bool|int|null $isFirstFrame
    ) {
        if (!$itemData) {
            throw new \Error('SVGElementsRenderer: Method renderContetTransform is missing data');
        }
        if ($isFirstFrame || $itemData->transform['op']->_mdf) {
            $itemData->transform['container']->setAttribute(
                'opacity',
                $itemData->transform['op']->v
            );
        }
        if ($isFirstFrame || $itemData->transform['mProps']->_mdf) {
            $itemData->transform['container']->setAttribute(
                'transform',
                $itemData->transform['mProps']->v->to2dCSS()
            );
        }
    }

    private static function renderFill(
        array $_,
        ?SVGFillStyleData $itemData,
        bool|int|null $isFirstFrame
    ) {
        if (!$itemData) {
            throw new \Error('SVGElementsRenderer: Method renderFill is missing data');
        }
        $styleElem = $itemData->style;

        if ($itemData->c?->v && ($itemData->c->_mdf || $isFirstFrame)) {
            $styleElem->pElem->setAttribute(
                'fill',
                toRGBString($itemData->c->v)
            );
        }
        if ($itemData->o?->_mdf || $isFirstFrame) {
            $styleElem->pElem->setAttribute('fill-opacity', $itemData->o?->v);
        }
    }

    private static function renderGradient(
        array $styleData,
        ?SVGGradientFillStyleData $itemData,
        bool|int|null $isFirstFrame
    ) {
        if (!$itemData) {
            throw new \Error('SVGElementsRenderer: Method renderGradient is missing data');
        }
        $gfill = $itemData->gf;
        $hasOpacity = $itemData->g?->_hasOpacity;
        $pt1 = $itemData->s?->v ?? [0, 0];
        $pt2 = $itemData->e?->v ?? [0, 0];

        if ($itemData->o?->_mdf || $isFirstFrame) {
            $attr = ShapeType::GradientFill === $styleData['ty'] ? 'fill-opacity' : 'stroke-opacity';

            $itemData->style?->pElem->setAttribute($attr, $itemData->o?->v);
        }
        if ($itemData->s?->_mdf || $isFirstFrame) {
            $attr1 = 1 === $styleData['t'] ? 'x1' : 'cx';
            $attr2 = 'x1' === $attr1 ? 'y1' : 'cy';

            $gfill?->setAttribute($attr1, $pt1[0]);
            $gfill?->setAttribute($attr2, $pt1[1]);
            if ($hasOpacity && !$itemData->g?->_collapsable) {
                $itemData->of?->setAttribute($attr1, $pt1[0]);
                $itemData->of?->setAttribute($attr2, $pt1[1]);
            }
        }
        $stops = null;
        $stop = null;

        if ($itemData->g && ($itemData->g->_cmdf || $isFirstFrame)) {
            $stops = $itemData->cst;
            $cValues = $itemData->g->c;
            $length = count($stops);

            for ($i = 0; $i < $length; ++$i) {
                $stop = $stops[$i];
                $stop->setAttribute('offset', "{$cValues[$i * 4]}%");
                $stop->setAttribute(
                    'stop-color',
                    "rgb({$cValues[$i * 4 + 1]},{$cValues[$i * 4 + 2]},{$cValues[$i * 4 + 3]})"
                );
            }
        }
        if ($hasOpacity && $itemData->g && ($itemData->g->_omdf || $isFirstFrame)) {
            $oValues = $itemData->g->o;

            if ($itemData->g->_collapsable) {
                $stops = $itemData->cst;
            } else {
                $stops = $itemData->ost;
            }
            $sLen = count($stops);

            for ($i = 0; $i < $sLen; ++$i) {
                $stop = $stops[$i];
                if (!$itemData->g->_collapsable) {
                    $stop->setAttribute('offset', "{$oValues[$i * 2]}%");
                }
                $stop->setAttribute('stop-opacity', "{$oValues[$i * 2 + 1]}");
            }
        }
        if (1 === $styleData['t']) {
            if ($itemData->e?->_mdf || $isFirstFrame) {
                $gfill?->setAttribute('x2', $pt2[0]);
                $gfill?->setAttribute('y2', $pt2[1]);
                if ($hasOpacity && !$itemData->g?->_collapsable) {
                    $itemData->of?->setAttribute('x2', $pt2[0]);
                    $itemData->of?->setAttribute('y2', $pt2[1]);
                }
            }
        } else {
            $rad = 0;

            if ($itemData->s?->_mdf || $itemData->e?->_mdf || $isFirstFrame) {
                $rad = sqrt(pow($pt1[0] - $pt2[0], 2) + pow($pt1[1] - $pt2[1], 2));
                $gfill?->setAttribute('r', $rad);
                if ($hasOpacity && !$itemData->g?->_collapsable) {
                    $itemData->of?->setAttribute('r', $rad);
                }
            }
            if (
                $itemData->e?->_mdf
                || $itemData->h?->_mdf
                || $itemData->a?->_mdf
                || $isFirstFrame
            ) {
                if (!$rad) {
                    $rad = sqrt(pow($pt1[0] - $pt2[0], 2) + pow($pt1[1] - $pt2[1], 2));
                }
                $ang = atan2($pt2[1] - $pt1[1], $pt2[0] - $pt1[0]);

                $percent = (int) $itemData->h?->v;

                if ($percent >= 1) {
                    $percent = 0.99;
                } elseif ($percent <= -1) {
                    $percent = -0.99;
                }
                $dist = $rad * $percent;
                $x = cos($ang + (int) $itemData->a?->v) * $dist + $pt1[0];
                $y = sin($ang + (int) $itemData->a?->v) * $dist + $pt1[1];

                $gfill?->setAttribute('fx', $x);
                $gfill?->setAttribute('fy', $y);
                if ($hasOpacity && !$itemData->g?->_collapsable) {
                    $itemData->of?->setAttribute('fx', $x);
                    $itemData->of?->setAttribute('fy', $y);
                }
            }
            // gfill->setAttribute('fy','200');
        }
    }

    private static function renderGradientStroke(
        array $styleData,
        SVGGradientFillStyleData|SVGGradientStrokeStyleData|null $itemData,
        bool|int|null $isFirstFrame
    ) {
        self::renderGradient(
            $styleData,
            $itemData,
            $isFirstFrame
        );
        self::renderStroke(
            $styleData,
            $itemData,
            $isFirstFrame
        );
    }

    private static function renderNoop(array $_)
    {
        // Pass Through
    }

    private static function renderPath(
        array $styleData,
        ?SVGShapeData $itemData,
        bool|int|null $isFirstFrame
    ) {
        if (!$itemData) {
            throw new \Error('SVGElementsRenderer: Method renderPath is missing data');
        }
        $pathStringTransformed = null;
        $shouldRedraw = false;
        $pathNodes = null;
        $caches = $itemData->caches ?? [];
        $lvl = $itemData->lvl ?? 0;
        $sh = $itemData->sh ?? null;
        $styles = $itemData->styles ?? [];
        $transformers = $itemData->transformers ?? [];

        $length = count($styles);
        $paths = null;
        $mat = null;
        $iterations = null;
        $k = 0;

        for ($i = 0; $i < $length; ++$i) {
            $shouldRedraw = (bool) ($sh?->_mdf ?? false) || (bool) $isFirstFrame;
            $style = $styles[$i];
            $styleLevel = (int) ($style?->lvl ?? 0);

            if ($styleLevel < $lvl) {
                if (!self::$_matrixHelper) {
                    self::$_matrixHelper = new Matrix();
                }

                $mat = self::$_matrixHelper->reset();
                $iterations = $lvl - $styleLevel;
                $k = count($transformers) - 1;
                while (!$shouldRedraw && $iterations > 0) {
                    $shouldRedraw = (bool) ($transformers[$k]['mProps']?->_mdf ?? false) || $shouldRedraw;
                    --$iterations;
                    --$k;
                }
                if ($shouldRedraw) {
                    $iterations = $lvl - $styleLevel;
                    $k = count($transformers) - 1;
                    while ($iterations > 0) {
                        $mat->multiply($transformers[$k]['mProps']?->v ?? 0);
                        --$iterations;
                        --$k;
                    }
                }
            } else {
                if (!self::$_identityMatrix) {
                    self::$_identityMatrix = new Matrix();
                }

                $mat = self::$_identityMatrix;
            }
            $paths = $sh?->paths ?? null;
            $jLen = $paths?->_length ?? 0;

            if ($shouldRedraw) {
                $pathStringTransformed = '';
                for ($j = 0; $j < $jLen; ++$j) {
                    $pathNodes = $paths?->shapes[$j];
                    if ($pathNodes?->_length) {
                        $pathStringTransformed .= buildShapeString(
                            $pathNodes,
                            $pathNodes->_length,
                            $pathNodes->c,
                            $mat
                        );
                    }
                }
                $caches[$i] = $pathStringTransformed;
            } else {
                $pathStringTransformed = $caches[$i];
            }
            if ($style) {
                $style->d .= true === ($styleData['hd'] ?? false) ? '' : ($pathStringTransformed ?? '');
                $style->_mdf = $shouldRedraw || (bool) ($style->_mdf ?? false);
            }
        }

        $itemData->caches = $caches;
    }

    private static function renderStroke(
        array $_,
        SVGGradientStrokeStyleData|SVGStrokeStyleData|null $itemData,
        bool|int|null $isFirstFrame
    ) {
        if (!$itemData?->style) {
            throw new \Error('SVGElementsRenderer: Method renderStroke is missing data');
        }

        $c = $itemData->c ?? null;
        $d = $itemData->d ?? null;
        $o = $itemData->o ?? null;
        $w = $itemData->w ?? null;
        $msElem = $itemData->style->msElem ?? null;
        $pElem = $itemData->style->pElem ?? null;

        if ($d && ($d->_mdf || $isFirstFrame) && $d->dashStr) {
            $pElem->setAttribute('stroke-dasharray', $d->dashStr);
            $pElem->setAttribute('stroke-dashoffset', $d->dashoffset[0]);
        }
        if ($c && ($c->_mdf || $isFirstFrame)) {
            $rgb = implode(',', array_map(fn ($color) => floor($color), $c->v));

            $pElem->setAttribute('stroke', "rgb({$rgb})");
        }
        if ($o?->_mdf || $isFirstFrame) {
            $pElem->setAttribute('stroke-opacity', $o?->v ?? 1);
        }
        if ($w?->_mdf || $isFirstFrame) {
            $pElem?->setAttribute('stroke-width', $w?->v ?? 0);
            $msElem?->setAttribute('stroke-width', $w?->v ?? 0);
        }
    }

    public static function createRenderFunction(array $data)
    {
        $ty = ShapeType::tryFrom($data['ty'] ?? '');

        switch ($ty) {
            case ShapeType::Fill:
                return self::renderFill(...);

            case ShapeType::GradientFill:
                return self::renderGradient(...);

            case ShapeType::GradientStroke:
                return self::renderGradientStroke(...);

            case ShapeType::Stroke:
                return self::renderStroke(...);

            case ShapeType::Path:
            case ShapeType::Ellipse:
            case ShapeType::Rectangle:
            case ShapeType::PolygonStar:
                return self::renderPath(...);

            case ShapeType::Transform:
                return self::renderContentTransform(...);

            case ShapeType::NoStyle:
                return self::renderNoop(...);

            default:
                return null;
        }
    }
    private static ?Matrix $_identityMatrix = null;
    private static ?Matrix $_matrixHelper = null;
}
