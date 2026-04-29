<?php

namespace LottiePHP\Effects;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Properties\BaseProperty;

class GroupEffect extends DynamicPropertyContainer
{
    public ?array $data;
    public array $effectElements = [];
    public \Closure $getValue;
    public ?string $type;

    public function __construct(
        array $data,
        BaseElement|BaseProperty $element,
        array $layer
    ) {
        $this->getValue = $this->iterateDynamicProperties(...);
        $this->init(
            $data,
            $element,
            $layer
        );
    }

    public function init(
        array $data,
        BaseElement|BaseProperty $element,
        array $layer
    ) {
        $this->data = $data;
        $this->effectElements = [];
        $this->initDynamicPropertyContainer($element);
        $eff = null;
        $effects = $this->data['ef'];
        $length = count($effects);

        for ($i = 0; $i < $length; ++$i) {
            // eff = null
            switch ($effects[$i]['ty'] ?? null) {
                case 0:
                    $eff = new SliderEffect(
                        $effects[$i],
                        $element,
                        $this
                    );

                    break;

                case 1:
                    $eff = new AngleEffect(
                        $effects[$i],
                        $element,
                        $this
                    );

                    break;

                case 2:
                    $eff = new ColorEffect(
                        $effects[$i],
                        $element,
                        $this
                    );

                    break;

                case 3:
                    $eff = new PointEffect(
                        $effects[$i],
                        $element,
                        $this
                    );

                    break;

                case 4:
                case 7:
                    $eff = new CheckboxEffect(
                        $effects[$i],
                        $element,
                        $this
                    );

                    break;

                case 10:
                    $eff = new LayerIndexEffect(
                        $effects[$i],
                        $element,
                        $this
                    );

                    break;

                case 11:
                    $eff = new MaskIndexEffect(
                        $effects[$i],
                        $element,
                        $this
                    );

                    break;

                case 5:
                    $eff = new EffectsManager($layer, $element);

                    break;

                default:
                    $eff = new NoValueEffect();

                    break;
            }
            array_push($this->effectElements, $eff);
        }
    }

    public function renderFrame(bool|int|null $_frame = null)
    {
        throw new \Error(get_class($this).': Method renderFrame is not implemented yet');
    }
}
