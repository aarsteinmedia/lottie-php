<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Bezier;
use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

use function LottiePHP\Utils\createTypedArray;

class KeyframedMultidimensionalProperty extends BaseProperty
{
    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        ?float $mult = null,
        BaseElement|BaseProperty|DynamicPropertyContainer|null $container = null
    ) {
        $this->initDynamicPropertyContainer($container ?? $elem);
        $this->propType = PropType::MultiDimensional;
        // Ensure a deterministic "init frame" so caching and initial values are numeric.
        // Otherwise `getValueAtCurrentTime()` can skip interpolation and leave nulls.
        $this->initFrame ??= Constants::$initialDefaultFrame;
        $length = count($data['k']);
        $s = null;
        $e = null;
        $to = null;
        $ti = null;

        for ($i = 0; $i < $length - 1; ++$i) {
            if (($data['k'][$i]['to'] ?? null) && ($data['k'][$i]['s'] ?? null) && ($data['k'][$i + 1]['s'] ?? null)) {
                $s = $data['k'][$i]['s'] ?? null;
                $e = $data['k'][$i + 1]['s'] ?? null;
                $to = $data['k'][$i]['to'] ?? null;
                $ti = $data['k'][$i]['ti'] ?? null;
                if (
                    2 === count($s)
                    && !($s[0] === $e[0] && $s[1] === $e[1])
                    && Bezier::pointOnLine2D(
                        $s[0] ?? 0,
                        $s[1] ?? 0,
                        $e[0] ?? 0,
                        $e[1] ?? 0,
                        $s[0] ?? 0 + ($to[0] ?? 0),
                        $s[1] ?? 0 + ($to[1] ?? 0)
                    )
                    && Bezier::pointOnLine2D(
                        $s[0] ?? 0,
                        $s[1] ?? 0,
                        $e[0] ?? 0,
                        $e[1] ?? 0,
                        $e[0] ?? 0 + ($ti[0] ?? 0),
                        $e[1] ?? 0 + ($ti[1] ?? 0)
                    )
                    || 3 === count($s)
                    && !($s[0] === $e[0] && $s[1] === $e[1] && $s[2] === $e[2])
                    && Bezier::pointOnLine3D(
                        $s[0] ?? 0,
                        $s[1] ?? 0,
                        $s[2] ?? 0,
                        $e[0] ?? 0,
                        $e[1] ?? 0,
                        $e[2] ?? 0,
                        $s[0] ?? 0 + ($to[0] ?? 0),
                        $s[1] ?? 0 + ($to[1] ?? 0),
                        $s[2] ?? 0 + ($to[2] ?? 0)
                    )
                    && Bezier::pointOnLine3D(
                        $s[0] ?? 0,
                        $s[1] ?? 0,
                        $s[2] ?? 0,
                        $e[0] ?? 0,
                        $e[1] ?? 0,
                        $e[2] ?? 0,
                        $e[0] ?? 0 + ($ti[0] ?? 0),
                        $e[1] ?? 0 + ($ti[1] ?? 0),
                        $e[2] ?? 0 + ($ti[2] ?? 0)
                    )
                ) {
                    $data['k'][$i]['to'] = null;
                    $data['k'][$i]['ti'] = null;
                }
                if (
                    $s[0] === $e[0]
                    && $s[1] === $e[1]
                    && 0 === $to[0]
                    && 0 === $to[1]
                    && 0 === $ti[0]
                    && 0 === $ti[1] && 2 === count($s) || ($s[2] ?? null) === ($e[2] ?? null) && 0 === ($to[2] ?? null) && 0 === ($ti[2] ?? null)
                ) {
                    $data['k'][$i]['to'] = null;
                    $data['k'][$i]['ti'] = null;
                }
            }
        }
        $this->data = $data;
        $this->keyframes = $data['k'];
        $this->keyframesMetadata = [];
        $this->offsetTime = is_object($elem)
            ? ($elem->data['st'] ?? 0)
            : ($elem['data']['st'] ?? 0);
        $this->k = true;
        $this->kf = true;
        $this->_isFirstFrame = true;
        // See `ValueProperty`: treat 0 as “default multiplier”.
        $this->mult = $mult ?: 1.0;
        $this->elem = $elem;
        $this->container = $container;
        $this->comp = is_object($elem) ? ($elem->comp ?? null) : ($elem['comp'] ?? null);
        $this->frameId = -1;
        $arrLen = count($data['k'][0]['s'] ?? []);

        $this->v = createTypedArray(ArrayType::Float32, $arrLen);
        $this->pv = createTypedArray(ArrayType::Float32, $arrLen);
        for ($i = 0; $i < $arrLen; ++$i) {
            $this->v[$i] = $this->initFrame;
            $this->pv[$i] = $this->initFrame;
        }
        // Drive evaluation through BaseProperty::processEffectsSequence().
        $this->effectsSequence = [fn ($_) => $this->getValueAtCurrentTime()];
        $this->_caching = [
            'lastFrame' => $this->initFrame,
            'lastIndex' => 0,
            'value' => createTypedArray(ArrayType::Float32, $arrLen),
        ];

        // Keyframed properties are animated; register with container so it ticks each frame.
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

    public function getValue(...$args)
    {
        return $this->processEffectsSequence(...$args);
    }
}
