<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

class KeyframeValueProperty extends BaseProperty
{
    public ?string $selectorValue;
    // BaseProperty defines `$v` already. Avoid redeclaring with a narrower type.

    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        ?float $mult = null,
        BaseElement|BaseProperty|DynamicPropertyContainer|null $container = null
    ) {
        $this->initDynamicPropertyContainer($container ?? $elem);
        $this->propType = PropType::UniDimensional;
        // Ensure a deterministic "init frame" so caching and initial values are numeric.
        // Otherwise `getValueAtCurrentTime()` can skip interpolation and leave nulls.
        $this->initFrame ??= Constants::$initialDefaultFrame;
        $this->keyframes = $data['k'];
        $this->keyframesMetadata = [];
        $this->offsetTime = is_object($elem)
            ? ($elem->data['st'] ?? 0)
            : ($elem['data']['st'] ?? 0);
        $this->frameId = -1;
        $this->_caching = [
            '_lastKeyframeIndex' => -1,
            'lastFrame' => $this->initFrame,
            'lastIndex' => 0,
            'value' => 0,
        ];
        $this->k = true;
        $this->kf = true;
        $this->data = $data;
        // See `ValueProperty`: treat 0 as “default multiplier”.
        $this->mult = $mult ?: 1.0;
        $this->elem = $elem;
        $this->container = $container;
        $this->comp = is_object($elem) ? ($elem->comp ?? null) : ($elem['comp'] ?? null);
        $this->v = $this->initFrame;
        $this->pv = $this->initFrame;
        // Drive evaluation through BaseProperty::processEffectsSequence().
        $this->effectsSequence = [fn ($_) => $this->getValueAtCurrentTime()];
        $this->_isFirstFrame = true;

        // Keyframe properties are animated; register with container so it ticks each frame.
        $containerRef = $container ?? $elem;
        if (is_object($containerRef) && method_exists($containerRef, 'addDynamicProperty')) {
            $containerRef->addDynamicProperty($this);
        } elseif (is_array($containerRef) && isset($containerRef['addDynamicProperty']) && is_callable($containerRef['addDynamicProperty'])) {
            ($containerRef['addDynamicProperty'])($this);
        }
    }

    public function effectsSequence(...$args)
    {
        return $this->getValueAtCurrentTime(...$args);
    }

    public function getValue(?bool $flag = null)
    {
        return $this->processEffectsSequence($flag);
    }
}
