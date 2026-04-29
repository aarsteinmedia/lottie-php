<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableElement;
use LottiePHP\Utils\Enums\EffectTypes;
use LottiePHP\Utils\FiltersFactory;
use LottiePHP\Utils\LocationHref;

use function LottiePHP\Utils\createElementID;

class SVGEffects
{
    public array $filters = [];

    public function __construct(RenderableElement $elem)
    {
        $source = 'SourceGraphic';

        $filId = createElementID();
        $fil = FiltersFactory::createFilter($filId, true);

        $count = 0;
        $filterManager = null;

        $length = count($elem->data['ef'] ?? []);

        for ($i = 0; $i < $length; ++$i) {
            $filterManager = null;

            ['ty' => $ty] = $elem->data['ef'][$i] ?? ['ty' => null];

            $Effect = (null === $ty || !(self::$registeredEffects[$ty] ?? null))
                ? null
                : (self::$registeredEffects[$ty]['effect'] ?? null);

            if ($Effect && $ty && $elem->effectsManager) {
                $args = [
                    $fil,
                    $elem->effectsManager->effectElements[$i],
                    $elem,
                    "{$this->idPrefix}{$count}",
                ];

                // Not all effects accept a `$source` input (e.g. `SVGGaussianBlurEffect`).
                // Only pass it when the target constructor supports it.
                $ctor = (new \ReflectionClass($Effect))->getConstructor();
                if ($ctor && $ctor->getNumberOfParameters() >= 5) {
                    $args[] = $source;
                }

                $filterManager = new ($Effect)(...$args);
                $source = "{$this->idPrefix}{$count}";
                if (self::$registeredEffects[$ty]['countsAsEffect'] ?? null) {
                    ++$count;
                }
            }
            if ($filterManager) {
                array_push($this->filters, $filterManager);
            }
        }
        if ($count) {
            $locationHref = LocationHref::getHref();

            $elem->globalData['defs']?->appendChild($fil);
            $elem->layerElement?->setAttribute(
                'filter',
                "url({$locationHref}#{$filId})"
            );
        }
        if (count($this->filters) > 0) {
            $elem->addRenderableComponent($this);
        }
    }

    public function getEffects(EffectTypes $type)
    {
        $length = count($this->filters);
        $effects = [];

        for ($i = 0; $i < $length; ++$i) {
            $filter = $this->filters[$i];
            if (is_object($filter) && property_exists($filter, 'type') && $filter->type === $type) {
                $effects[] = $filter;
            }
        }

        return $effects;
    }

    /**
     * @param class-string<\LottiePHP\Effects/EffectOne> $effect
     */
    public static function registerEffect(int $id, string $effect, bool $countsAsEffect = false)
    {
        self::$registeredEffects[$id] = [
            'countsAsEffect' => $countsAsEffect,
            'effect' => $effect,
        ];
    }

    public function renderFrame(false|int|null $frame = null)
    {
        $length = count($this->filters);

        for ($i = 0; $i < $length; ++$i) {
            $this->filters[$i]->renderFrame($frame);
        }
    }
    private string $idPrefix = 'filter_result_';
    private static array $registeredEffects = [];
}
