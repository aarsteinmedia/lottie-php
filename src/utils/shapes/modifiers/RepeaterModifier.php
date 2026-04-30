<?php

namespace LottiePHP\Utils\Shapes\Modifiers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\ShapeType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Matrix;
use LottiePHP\Utils\Properties\TransformProperty;
use LottiePHP\Utils\Properties\TransformPropertyFactory;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;

class RepeaterModifier extends ShapeModifier
{
    public $arr = [];
    public ?ValueProperty $c;
    public ?array $data;
    public $elemsData = [];
    public ?ValueProperty $eo;
    public ?Matrix $matrix;
    public ?ValueProperty $o;
    public ?Matrix $pMatrix;
    public ?int $pos;
    public ?Matrix $rMatrix;
    public ?Matrix $sMatrix;
    public ?ValueProperty $so;
    public ?Matrix $tMatrix;
    public ?TransformProperty $tr;

    public function addShapeToModifier(array|object $shapeData)
    {
        if (is_array($shapeData)) {
            $shapeData['pathsData'] = [];

            return;
        }
        // modifier shape entries are objects (see ShapeModifier::addShape)
        $shapeData->pathsData = [];
    }

    public function applyTransforms(
        Matrix $pMatrix,
        Matrix $rMatrix,
        Matrix $sMatrix,
        TransformProperty $transform,
        int $perc,
        ?bool $inv
    ) {
        if (!$transform->s || !$transform->p || !$transform->a || !$transform->r) {
            throw new \Error(get_class($this).': Missing required data from Transform');
        }
        $dir = $inv ? -1 : 1;
        $scaleX = $transform->s->v[0] + (1 - $transform->s->v[0]) * (1 - $perc);
        $scaleY = $transform->s->v[1] + (1 - $transform->s->v[1]) * (1 - $perc);

        $pMatrix->translate(
            $transform->p->v[0] * $dir * $perc,
            $transform->p->v[1] * $dir * $perc,
            $transform->p->v[2]
        );
        $rMatrix->translate(
            -$transform->a->v[0],
            -$transform->a->v[1],
            $transform->a->v[2]
        );
        $rMatrix->rotate(-$transform['r']->v * $dir * $perc);
        $rMatrix->translate(
            $transform->a->v[0] ?? 0,
            $transform->a->v[1] ?? 0,
            $transform->a->v[2]
        );
        $sMatrix->translate(
            -($transform->a->v[0] ?? 0),
            -($transform->a->v[1] ?? 0),
            $transform->a->v[2]
        );
        $sMatrix->scale($inv ? 1 / $scaleX : $scaleX, $inv ? 1 / $scaleY : $scaleY);
        $sMatrix->translate(
            $transform->a->v[0] ?? 0,
            $transform->a->v[1] ?? 0,
            $transform->a->v[2]
        );
    }

    public function changeGroupRender(array $elements, $renderFlag = false)
    {
        $length = count($elements);

        for ($i = 0; $i < $length; ++$i) {
            if (!is_array($elements[$i])) {
                continue;
            }
            $elements[$i]['_shouldRender'] = $renderFlag;
            if (ShapeType::Group === ShapeType::tryFrom($elements[$i]['ty'] ?? null)) {
                $this->changeGroupRender($elements[$i]['it'] ?? [], $renderFlag);
            }
        }
    }

    public function cloneElements(array $elements): array
    {
        // Avoid JSON-based deep cloning: it can explode memory for large repeaters.
        // PHP arrays are copy-on-write; a shallow assignment is enough until mutated.
        $newElements = $elements;
        $this->resetElements($newElements);

        return $newElements;
    }

    public function getValue(...$args)
    {
        return $this->processKeys(...$args);
    }

    public function init(
        BaseElement|DynamicPropertyContainer $elem,
        array $arr,
        ?int $posFromProps = 0,
        $elemsData = []
    ) {
        if (!is_array($arr)) {
            throw new \TypeError(get_class($this).': Method init, param arr must be array');
        }
        $pos = (int) $posFromProps;

        $this->elem = $elem;
        // Keep a reference to the live shape array. Repeaters expand the shape tree,
        // and the expansion must persist across `reloadShapes()` calls to avoid
        // infinite expansion loops / OOM.
        $this->arr = &$arr;
        $this->pos = $pos;
        $this->elemsData = $elemsData;
        $this->_currentCopies = 0;
        $this->_elements = [];
        $this->_groups = [];
        $this->frameId = -1;
        $this->initDynamicPropertyContainer($elem);
        $this->initModifierProperties($elem, $arr[$pos]);
        // Detect already-inserted groups for this repeater (prevents re-expanding on every reload).
        if (!isset($arr[$this->pos]['_rpIdSelf']) || !is_string($arr[$this->pos]['_rpIdSelf'])) {
            $seed = [
                'ix' => $arr[$this->pos]['ix'] ?? null,
                'nm' => $arr[$this->pos]['nm'] ?? null,
                'c' => $arr[$this->pos]['c']['k'] ?? null,
                'o' => $arr[$this->pos]['o']['k'] ?? null,
            ];
            $arr[$this->pos]['_rpIdSelf'] = md5(json_encode($seed));
        }
        $rpId = (string) $arr[$this->pos]['_rpIdSelf'];
        $inserted = 0;
        while (isset($arr[$inserted]) && is_array($arr[$inserted]) && (($arr[$inserted]['_rpId'] ?? null) === $rpId)) {
            $this->_groups[] = $arr[$inserted];
            ++$inserted;
        }
        $this->_currentCopies = count($this->_groups);

        // Original elements are those between the already-inserted groups and the repeater itself.
        for ($k = $this->pos - 1; $k >= $inserted; --$k) {
            array_unshift($this->_elements, $arr[$k]);
        }
        if (count($this->dynamicProperties) > 0) {
            $this->k = true;

            return;
        }
        $this->getValue(true);
    }

    public function initModifierProperties(
        BaseElement|DynamicPropertyContainer $elem,
        array $data
    ) {
        $this->c = PropertyFactory::getProp(
            $elem,
            $data['c'],
            0,
            null,
            $this
        );
        $this->o = PropertyFactory::getProp(
            $elem,
            $data['o'],
            0,
            null,
            $this
        );
        if ($data['tr']) {
            $this->tr = TransformPropertyFactory::getTransformProperty(
                $elem,
                $data['tr'],
                $this
            );
            $this->so = PropertyFactory::getProp(
                $elem,
                $data['tr']['so'],
                0,
                0.01,
                $this
            );
            $this->eo = PropertyFactory::getProp(
                $elem,
                $data['tr']['eo'],
                0,
                0.01,
                $this
            );
        }

        $this->data = $data;
        if (0 === count($this->dynamicProperties)) {
            $this->getValue(true);
        }
        $this->_isAnimated = count($this->dynamicProperties) > 0;
        $this->pMatrix = new Matrix();
        $this->rMatrix = new Matrix();
        $this->sMatrix = new Matrix();
        $this->tMatrix = new Matrix();
        $this->matrix = new Matrix();
    }

    public function processShapes(bool $_isFirstFrame)
    {
        if (!$this->data) {
            throw new \Error(get_class($this).':  data (Shape) is not implemented');
        }

        $items = null;
        $itemsTransform = null;
        $i = 0;
        $dir = null;
        $cont = 0;
        $hasReloaded = false;

        if (!$this->_mdf && !$_isFirstFrame) {
            $cont = (int) $this->_currentCopies;
            $i = 0;
            $dir = 1;
            while ($cont) {
                $items = is_array($this->elemsData[$i] ?? null) ? ($this->elemsData[$i]['it'] ?? []) : [];
                // if (!items) {
                //   continue
                // }
                // itemsTransform = items[count($items) - 1].transform.mProps.v.props
                $last = $items[count($items) - 1] ?? null;
                if (is_object($last) && isset($last->transform['mProps'], $last->transform['op'])) {
                    $last->transform['mProps']->_mdf = false;
                    $last->transform['op']->_mdf = false;
                }
                --$cont;
                $i += $dir;
            }

            return $hasReloaded;
        }

        $copies = (int) ceil((float) ($this->c?->v ?? 0));
        // Safety: avoid runaway memory in still SVG rendering.
        $maxCopies = (int) ($this->elem?->globalData['renderConfig']['maxRepeaterCopies'] ?? 150);
        $maxExpandCopies = (int) ($this->elem?->globalData['renderConfig']['maxRepeaterExpandCopies'] ?? 25);
        if ($copies > $maxCopies) {
            $copies = $maxCopies;
        } elseif ($copies < 0) {
            $copies = 0;
        }
        // Expanding large repeaters requires duplicating the shape tree and is too memory-hungry
        // for our still SVG renderer. Degrade gracefully by not expanding.
        if ($copies > $maxExpandCopies) {
            $this->_currentCopies = 1;

            return false;
        }

        if (count($this->_groups) < $copies) {
            while (count($this->_groups) < $copies) {
                $group = [
                    'it' => $this->cloneElements($this->_elements),
                    'ty' => 'gr',
                    '_rpId' => $rpId,
                ];

                $group['it'][] = [
                    'a' => [
                        'a' => 0,
                        'ix' => 1,
                        'k' => [0, 0],
                    ],
                    'nm' => 'Transform',
                    'o' => [
                        'a' => 0,
                        'ix' => 7,
                        'k' => 100,
                    ],
                    'p' => [
                        'a' => 0,
                        'ix' => 2,
                        'k' => [0, 0],
                    ],
                    'r' => [
                        'a' => 1,
                        'ix' => 6,
                        'k' => [
                            [
                                'e' => 0,
                                's' => 0,
                                't' => 0,
                            ],
                            [
                                'e' => 0,
                                's' => 0,
                                't' => 1,
                            ],
                        ],
                    ],
                    's' => [
                        'a' => 0,
                        'ix' => 3,
                        'k' => [100, 100],
                    ],
                    'sa' => [
                        'a' => 0,
                        'ix' => 5,
                        'k' => 0,
                    ],
                    'sk' => [
                        'a' => 0,
                        'ix' => 4,
                        'k' => 0,
                    ],
                    'ty' => ShapeType::Transform,
                ];

                // Insert new groups at the start; this is what lottie-web does and
                // matches our "already inserted groups" detection.
                array_unshift($this->arr, $group);
                $this->_groups[] = $group;
                ++$this->_currentCopies;
            }
            $this->elem?->reloadShapes();
            $hasReloaded = true;
        }
        $cont = 0;
        $shouldRender = false;
        $length = count($this->_groups) - 1;

        for ($i = 0; $i <= $length - 1; ++$i) {
            $shouldRender = $cont < $copies;
            if ($this->_groups[$i]) {
                $this->_groups[$i]['_shouldRender'] = $shouldRender;
            }

            $this->changeGroupRender($this->_groups[$i]['it'] ?? [], $shouldRender);
            if (!$shouldRender) {
                $elems = is_array($this->elemsData[$i] ?? null) ? ($this->elemsData[$i]['it'] ?? []) : [];
                $transformData = $elems[count($elems) - 1];

                if ($transformData) {
                    if (0 === $transformData->transform->op->v) {
                        $transformData->transform->op->_mdf = false;
                    } else {
                        $transformData->transform->op->_mdf = true;
                        $transformData->transform->op->v = 0;
                    }
                }
            }
            ++$cont;
        }

        $this->_currentCopies = $copies;

        if (
            !$this->matrix
            || !$this->pMatrix
            || !$this->rMatrix
            || !$this->sMatrix
            || !$this->tMatrix
        ) {
            throw new \Error(get_class($this).':  Could not set Matrix');
        }

        if (!$this->tr) {
            throw new \Error(get_class($this).':  Transformproperty is not set');
        }

        $offset = (float) $this->o?->v;
        $offsetModulo = $offset % 1;
        $roundOffset = $offset > 0 ? floor($offset) : ceil($offset);
        $pProps = $this->pMatrix->props;
        $rProps = $this->rMatrix->props;
        $sProps = $this->sMatrix->props;

        $this->pMatrix->reset();
        $this->rMatrix->reset();
        $this->sMatrix->reset();
        $this->tMatrix->reset();
        $this->matrix->reset();
        $iteration = 0;

        if ($offset > 0) {
            while ($iteration < $roundOffset) {
                $this->applyTransforms(
                    $this->pMatrix,
                    $this->rMatrix,
                    $this->sMatrix,
                    $this->tr,
                    1,
                    false
                );
                ++$iteration;
            }
            if ($offsetModulo) {
                $this->applyTransforms(
                    $this->pMatrix,
                    $this->rMatrix,
                    $this->sMatrix,
                    $this->tr,
                    $offsetModulo,
                    false
                );
                $iteration += $offsetModulo;
            }
        } elseif ($offset < 0) {
            while ($iteration > $roundOffset) {
                $this->applyTransforms(
                    $this->pMatrix,
                    $this->rMatrix,
                    $this->sMatrix,
                    $this->tr,
                    1,
                    true
                );
                --$iteration;
            }
            if ($offsetModulo) {
                $this->applyTransforms(
                    $this->pMatrix,
                    $this->rMatrix,
                    $this->sMatrix,
                    $this->tr,
                    -$offsetModulo,
                    true
                );
                $iteration -= $offsetModulo;
            }
        }
        $i = 1 === $this->data['m'] ? 0 : $this->_currentCopies - 1;
        $dir = 1 === $this->data['m'] ? 1 : -1;
        $cont = $this->_currentCopies;
        $j = 0;

        while ($cont) {
            $items = $this->elemsData[$i]?->it ?? [];
            $itemsTransform = $items[count($items) - 1]?->transform->mProps->v->props ?? [];
            $jLen = count($itemsTransform);

            if ($items[count($items) - 1]) {
                $items[count($items) - 1]['transform']['mProps']->_mdf = true;
                $items[count($items) - 1]['transform']['op']['_mdf'] = true;
                $items[count($items) - 1]['transform']['op']->v
                    = 1 === $this->_currentCopies
                      ? (float) ($this->so?->v)
                      : (float) ($this->so?->v)
                        + ((float) $this->eo?->v - (float) $this->so?->v)
                        * ($i / ($this->_currentCopies - 1));
            }

            if (0 === $iteration) {
                $this->matrix->reset();
                for ($j = 0; $j < $jLen; ++$j) {
                    $itemsTransform[$j] = $this->matrix->props[$j] ?? 0;
                }
            } else {
                if (
                    0 !== $i && 1 === $dir
                    || $i !== $this->_currentCopies - 1 && -1 === $dir
                ) {
                    $this->applyTransforms(
                        $this->pMatrix,
                        $this->rMatrix,
                        $this->sMatrix,
                        $this->tr,
                        1,
                        false
                    );
                }
                $this->matrix->transform(
                    $rProps[0] ?? 0,
                    $rProps[1] ?? 0,
                    $rProps[2] ?? 0,
                    $rProps[3] ?? 0,
                    $rProps[4] ?? 0,
                    $rProps[5] ?? 0,
                    $rProps[6] ?? 0,
                    $rProps[7] ?? 0,
                    $rProps[8] ?? 0,
                    $rProps[9] ?? 0,
                    $rProps[10] ?? 0,
                    $rProps[11] ?? 0,
                    $rProps[12] ?? 0,
                    $rProps[13] ?? 0,
                    $rProps[14] ?? 0,
                    $rProps[15] ?? 0
                );
                $this->matrix->transform(
                    $sProps[0] ?? 0,
                    $sProps[1] ?? 0,
                    $sProps[2] ?? 0,
                    $sProps[3] ?? 0,
                    $sProps[4] ?? 0,
                    $sProps[5] ?? 0,
                    $sProps[6] ?? 0,
                    $sProps[7] ?? 0,
                    $sProps[8] ?? 0,
                    $sProps[9] ?? 0,
                    $sProps[10] ?? 0,
                    $sProps[11] ?? 0,
                    $sProps[12] ?? 0,
                    $sProps[13] ?? 0,
                    $sProps[14] ?? 0,
                    $sProps[15] ?? 0
                );
                $this->matrix->transform(
                    $pProps[0] ?? 0,
                    $pProps[1] ?? 0,
                    $pProps[2] ?? 0,
                    $pProps[3] ?? 0,
                    $pProps[4] ?? 0,
                    $pProps[5] ?? 0,
                    $pProps[6] ?? 0,
                    $pProps[7] ?? 0,
                    $pProps[8] ?? 0,
                    $pProps[9] ?? 0,
                    $pProps[10] ?? 0,
                    $pProps[11] ?? 0,
                    $pProps[12] ?? 0,
                    $pProps[13] ?? 0,
                    $pProps[14] ?? 0,
                    $pProps[15] ?? 0
                );

                for ($j = 0; $j < $jLen; ++$j) {
                    $itemsTransform[$j] = $this->matrix->props[$j] ?? 0;
                }
                $this->matrix->reset();
            }
            ++$iteration;
            --$cont;
            $i += $dir;
        }

        return $hasReloaded;
    }

    public function resetElements(array &$elements): void
    {
        $length = count($elements);

        for ($i = 0; $i < $length; ++$i) {
            if (!is_array($elements[$i])) {
                continue;
            }
            $elements[$i]['_processed'] = false;

            $it = $elements[$i]['it'] ?? null;

            if (ShapeType::Group === ShapeType::tryFrom($elements[$i]['ty'] ?? null) && $it) {
                $this->resetElements($elements[$i]['it']);
            }
        }
    }
    private ?int $_currentCopies;
    private array $_elements = [];
    private array $_groups = [];
}
