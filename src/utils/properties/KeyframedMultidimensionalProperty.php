<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Bezier;
use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Enums\PropType;

use function LottiePHP\Utils\createTypedArray;

class KeyframedMultidimensionalProperty extends BaseProperty
{
    public $v;
    public function __construct(
        BaseProperty $elem,
        array $data,
        ?int $mult = null,
        ?BaseElement $container = null
    ) {
        $this->propType = PropType::MultiDimensional;
        $length = count($data['k']);
        $s = null;
        $e = null;
        $to = null;
        $ti = null;

        for ($i = 0; $i < $length - 1; $i++) {
            if (($data['k'][$i]['to'] ?? null) && ($data['k'][$i]['s'] ?? null) && ($data['k'][$i + 1]['s'] ?? null)) {
                $s = $data['k'][$i]['s'] ?? null;
                $e = $data['k'][$i + 1]['s'] ?? null;
                $to = $data['k'][$i]['to'] ?? null;
                $ti = $data['k'][$i]['ti'] ?? null;
                if (
                    count($s) === 2 &&
                    !($s[0] === $e[0] && $s[1] === $e[1]) &&
                    Bezier::pointOnLine2D(
                        $s[0] ?? 0,
                        $s[1] ?? 0,
                        $e[0] ?? 0,
                        $e[1] ?? 0,
                        $s[0] ?? 0 + ($to[0] ?? 0),
                        $s[1] ?? 0 + ($to[1] ?? 0)
                    ) &&
                    Bezier::pointOnLine2D(
                        $s[0] ?? 0,
                        $s[1] ?? 0,
                        $e[0] ?? 0,
                        $e[1] ?? 0,
                        $e[0] ?? 0 + ($ti[0] ?? 0),
                        $e[1] ?? 0 + ($ti[1] ?? 0)
                    ) ||
                    count($s) === 3 &&
                    !($s[0] === $e[0] && $s[1] === $e[1] && $s[2] === $e[2]) &&
                    Bezier::pointOnLine3D(
                        $s[0] ?? 0,
                        $s[1] ?? 0,
                        $s[2] ?? 0,
                        $e[0] ?? 0,
                        $e[1] ?? 0,
                        $e[2] ?? 0,
                        $s[0] ?? 0 + ($to[0] ?? 0),
                        $s[1] ?? 0 + ($to[1] ?? 0),
                        $s[2] ?? 0 + ($to[2] ?? 0)
                    ) &&
                    Bezier::pointOnLine3D(
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
                    $s[0] === $e[0] &&
                    $s[1] === $e[1] &&
                    $to[0] === 0 &&
                    $to[1] === 0 &&
                    $ti[0] === 0 &&
                    $ti[1] === 0 && count($s) === 2 || $s[2] === $e[2] && $to[2] === 0 && $ti[2] === 0
                ) {
                    $data['k'][$i]['to'] = null;
                    $data['k'][$i]['ti'] = null;
                }
            }
        }
        $this->data = $data;
        $this->keyframes = $data['k'];
        $this->keyframesMetadata = [];
        $this->offsetTime = $elem['data']['st'];
        $this->k = true;
        $this->kf = true;
        $this->_isFirstFrame = true;
        $this->mult = $mult || 1;
        $this->elem = $elem;
        $this->container = $container;
        $this->comp = $elem['comp'];
        $this->frameId = -1;
        $arrLen = count($data['k'][0]['s'] ?? []);

        $this->v = createTypedArray(ArrayType::Float32, $arrLen);
        $this->pv = createTypedArray(ArrayType::Float32, $arrLen);
        for ($i = 0; $i < $arrLen; $i++) {
            $this->v[$i] = $this->initFrame;
            $this->pv[$i] = $this->initFrame;
        }
        $this->_caching = [
          'lastFrame' => $this->initFrame,
          'lastIndex' => 0,
          'value' => createTypedArray(ArrayType::Float32, $arrLen),
        ];
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
