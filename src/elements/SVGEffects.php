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
    private $idPrefix = 'filter_result_';
    private static $registeredEffects = [];

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
            $Effect = null === $ty || !(self::$registeredEffects[$ty] ?? null) ? null : self::$registeredEffects[$ty]['effect'];

            if ($Effect && $ty && $elem->effectsManager) {
                $filterManager = new ($Effect)(
                    $fil,
                    $elem->effectsManager['effectElements'][$i],
                    $elem,
                    "{$this->idPrefix}{$count}",
                    $source
                );
                $source = "{$this->idPrefix}{$count}";
                if ($registeredEffects[$ty]['countsAsEffect'] ?? null) {
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
            if (($this->filters[$i]['type'] ?? null) === $type) {
                array_push($effects, $this->filters[$i]);
            }
        }

        return $effects;
    }

    public function renderFrame(false|int|null $frame)
    {
        $length = $this->filters;

        for ($i = 0; $i < $length; ++$i) {
            $this->filters[$i]->renderFrame($frame);
        }
    }

    public static function registerEffect(int $id, array $effect, $countsAsEffect = false)
    {
        self::$registeredEffects[$id] = [
            'countsAsEffect' => $countsAsEffect,
            'effect' => $effect,
        ];
    }
}
