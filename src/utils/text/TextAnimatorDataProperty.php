<?php

namespace LottiePHP\Utils\Text;

class TextAnimatorDataProperty {
  public ?array $a;
  public ?TextSelectorProperty $s;
  
  public function __construct(
    array $elem,
    ?TextAnimatorDataProperty $animatorProps,
    ?array $container
  ) {
    $defaultData = [ 'propType' => false ];
    $textAnimatorAnimatables = $animatorProps['a'] ?? null;

    $degToRads = M_PI / 180;

    $this->a = [
      'a' => ($textAnimatorAnimatables['a'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['a'],
          1,
          0,
          $container
        ))
        : $defaultData,
      'fb' => ($textAnimatorAnimatables['fb'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['fb'],
          0,
          0.01,
          $container
        ))
        : $defaultData,
      'fc' => ($textAnimatorAnimatables['fc'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['fc'],
          1,
          0,
          $container
        ))
        : $defaultData,
      'fh' => ($textAnimatorAnimatables['fh'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['fh'],
          0,
          0,
          $container
        ))
        : $defaultData,
      'fs' => ($textAnimatorAnimatables['fs'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['fs'],
          0,
          0.01,
          $container
        ))
        : $defaultData,
      'o' => ($textAnimatorAnimatables['o'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['o'],
          0,
          0.01,
          $container
        ))
        : $defaultData,
      'p' => ($textAnimatorAnimatables['p'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['p'],
          1,
          0,
          $container
        ))
        : $defaultData,
      'r' => ($textAnimatorAnimatables['r'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['r'],
          0,
          $degToRads,
          $container
        ))
        : $defaultData,
      'rx' => ($textAnimatorAnimatables['rx'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['rx'],
          0,
          $degToRads,
          $container
        ))
        : $defaultData,
      'ry' => ($textAnimatorAnimatables['ry'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['ry'],
          0,
          $degToRads,
          $container
        ))
        : $defaultData,
      's' => ($textAnimatorAnimatables['s'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['s'],
          1,
          0.01,
          $container
        ))
        : $defaultData,
      'sa' => ($textAnimatorAnimatables['sa'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['sa'],
          0,
          $degToRads,
          $container
        ))
        : $defaultData,
      'sc' => ($textAnimatorAnimatables['sc'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['sc'],
          1,
          0,
          $container
        ))
        : $defaultData,
      'sk' => ($textAnimatorAnimatables['sk'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['sk'],
          0,
          $degToRads,
          $container
        ))
        : $defaultData,
      'sw' => ($textAnimatorAnimatables['sw'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['sw'],
          0,
          0,
          $container
        ))
        : $defaultData,
      't' => ($textAnimatorAnimatables['t'] ?? null)
        ? (PropertyFactory::getProp(
          $elem,
          $textAnimatorAnimatables['t'],
          0,
          0,
          $container
        ))
        : $defaultData
     ];

    $this->s = new TextSelectorProperty($elem, $animatorProps['s'] ?? null);
    $this->s['t'] = $animatorProps['s']['t'] ?? null;
  }
}
