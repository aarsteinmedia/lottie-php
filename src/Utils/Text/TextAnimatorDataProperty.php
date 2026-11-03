<?php

namespace LottiePHP\Utils\Text;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\PropertyFactory;

class TextAnimatorDataProperty
{
    public ?array $a;
    public ?TextSelectorProperty $s;

    public function __construct(
        BaseElement|BaseProperty $elem,
        array|TextAnimatorDataProperty|null $animatorProps,
        ?TextAnimatorProperty $container
    ) {
        $defaultData = ['propType' => false];
        $textAnimatorItems = $animatorProps['a'] ?? null;

        $degToRads = M_PI / 180;

        $this->a = [
            'a' => ($textAnimatorItems['a'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['a'],
                  1,
                  0,
                  $container
              ))
              : $defaultData,
            'fb' => ($textAnimatorItems['fb'] ?? null)
              ? PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['fb'],
                  0,
                  0.01,
                  $container
              )
              : $defaultData,
            'fc' => ($textAnimatorItems['fc'] ?? null)
              ? PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['fc'],
                  1,
                  0,
                  $container
              )
              : $defaultData,
            'fh' => ($textAnimatorItems['fh'] ?? null)
              ? PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['fh'],
                  0,
                  0,
                  $container
              )
              : $defaultData,
            'fs' => ($textAnimatorItems['fs'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['fs'],
                  0,
                  0.01,
                  $container
              ))
              : $defaultData,
            'o' => ($textAnimatorItems['o'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['o'],
                  0,
                  0.01,
                  $container
              ))
              : $defaultData,
            'p' => ($textAnimatorItems['p'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['p'],
                  1,
                  0,
                  $container
              ))
              : $defaultData,
            'r' => ($textAnimatorItems['r'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['r'],
                  0,
                  $degToRads,
                  $container
              ))
              : $defaultData,
            'rx' => ($textAnimatorItems['rx'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['rx'],
                  0,
                  $degToRads,
                  $container
              ))
              : $defaultData,
            'ry' => ($textAnimatorItems['ry'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['ry'],
                  0,
                  $degToRads,
                  $container
              ))
              : $defaultData,
            's' => ($textAnimatorItems['s'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['s'],
                  1,
                  0.01,
                  $container
              ))
              : $defaultData,
            'sa' => ($textAnimatorItems['sa'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['sa'],
                  0,
                  $degToRads,
                  $container
              ))
              : $defaultData,
            'sc' => ($textAnimatorItems['sc'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['sc'],
                  1,
                  0,
                  $container
              ))
              : $defaultData,
            'sk' => ($textAnimatorItems['sk'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['sk'],
                  0,
                  $degToRads,
                  $container
              ))
              : $defaultData,
            'sw' => ($textAnimatorItems['sw'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['sw'],
                  0,
                  0,
                  $container
              ))
              : $defaultData,
            't' => ($textAnimatorItems['t'] ?? null)
              ? (PropertyFactory::getProp(
                  $elem,
                  $textAnimatorItems['t'],
                  0,
                  0,
                  $container
              ))
              : $defaultData,
        ];

        $this->s = new TextSelectorProperty($elem, $animatorProps['s'] ?? []);
        $this->s->t = $animatorProps['s']['t'] ?? null;
    }
}
