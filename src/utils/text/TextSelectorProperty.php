<?php

namespace LottiePHP\Utils\Text;

class TextSelectorProperty extends BaseProperty {
  public $_currentTextLength = -1;
  public ValueProperty $a;
  public ?ValueProperty $b;
  public array $data;
  public ValueProperty|array $e;
  public array $elem;
  public $finalE = 0;
  public $finalS = 0;
  public ValueProperty $ne;
  public ValueProperty $o;
  public ?int $rn;
  public ValueProperty $sm;
  public ?int $totalChars;
  public ValueProperty $xe;

  public function __construct(array $elem, array $data) {
    parent::__construct();
    $this->k = false;
    $this->data = $data;
    $this->elem = $elem;
    $this->comp = $elem['comp'];
    $this->initDynamicPropertyContainer($elem);
    $this->s = PropertyFactory::getProp(
      $elem,
      $data.s ?? [
        'a' => 0,
        'k' => 0
      ],
      0,
      0,
      $this
    );
    if (array_key_exists('e', $data)) {
      $this->e = PropertyFactory::getProp(
        $elem,
        $data['e'],
        0,
        0,
        $this
      );
    } else {
      $this->e = [ 'v' => 100 ];
    }
    $this->o = PropertyFactory::getProp(
      $elem,
      $data['o'] ?? [
        'a' => 0,
        'k' => 0
      ],
      0,
      0,
      $this
    );
    $this->xe = PropertyFactory::getProp(
      $elem,
      $data['xe'] ?? [
        'a' => 0,
        'k' => 0
      ],
      0,
      0,
      $this
    );
    $this->ne = PropertyFactory::getProp(
      $elem,
      $data['ne'] ?? [
        'a' => 0,
        'k' => 0
      ],
      0,
      0,
      $this
    );
    $this->sm = PropertyFactory::getProp(
      $elem,
      $data['sm'] ?? [
        'a' => 0,
        'k' => 100
      ],
      0,
      0,
      $this
    );
    $this->a = PropertyFactory::getProp(
      $elem,
      $data['a'],
      0,
      0.01,
      $this
    );

    if (count($this->dynamicProperties) === 0) {
      $this->getValue();
    }
  }

  public function getMult(int $indFromProps, ?int $_val = null): int|array {
    $ind = $indFromProps;

    if (
      $this->_currentTextLength !== count($this->elem['textProperty']['currentData']['l'] ?? [])
    ) {
      $this->getValue();
    }
    $x1 = 0;
      $y1 = 0;
      $x2 = 1;
      $y2 = 1;

    if ($this->ne['v'] > 0) {
      $x1 = $this->ne['v'] / 100.0;
    } else {
      $y1 = -$this->ne['v'] / 100.0;
    }
    if ($this->xe['v'] > 0) {
      $x2 = 1.0 - $this->xe['v'] / 100.0;
    } else {
      $y2 = 1.0 + $this->xe['v'] / 100.0;
    }
    $easer = getBezierEasing(
      $x1, $y1, $x2, $y2
    )->get;

    $mult = 0;
    $s = $this->finalS;
    $e = $this->finalE;
    $type = $this->data['sh'];

    switch ($type) {
      case 2: {
        if ($e === $s) {
          $mult = $ind >= $e ? 1 : 0;
        } else {
          $mult = max(0, min(0.5 / ($e - $s) + ($ind - $s) / ($e - $s), 1));
        }
        $mult = ($easer)($mult);
        break;
      }
      case 3: {
        if ($e === $s) {
          $mult = $ind >= $e ? 0 : 1;
        } else {
          $mult =
            1 - max(0, min(0.5 / ($e - $s) + ($ind - $s) / ($e - $s), 1));
        }

        $mult = ($easer)($mult);
        break;
      }
      case 4: {
        if ($e === $s) {
          $mult = 0;
        } else {
          $mult = max(0, min(0.5 / ($e - $s) + ($ind - $s) / ($e - $s), 1));
          if ($mult < 0.5) {
            $mult *= 2;
          } else {
            $mult = 1 - 2 * ($mult - 0.5);
          }
        }
        $mult = ($easer)($mult);
        break;
      }
      case 5: {
        if ($e === $s) {
          $mult = 0;
        } else {
          $tot = $e - $s;

          $ind = min(max(0, $ind + 0.5 - $s), $e - $s);
          $x = -$tot / 2 + $ind;
          $a = $tot / 2;

          $mult = sqrt(1 - $x * $x / ($a * $a));
        }
        $mult = ($easer)($mult);
        break;
      }
      case 6: {
        if ($e === $s) {
          $mult = 0;
        } else {
          $ind = min(max(0, $ind + 0.5 - $s), $e - $s);
          $mult = (1 + cos(M_PI + M_PI * 2 * $ind / ($e - $s))) / 2;
        }
        $mult = ($easer)($mult);
        break;
      }

      default: {
        if ($ind >= floor($s)) {
          if ($ind - $s < 0) {
            $mult = max(0, min(min($e, 1) - ($s - $ind), 1));
          } else {
            $mult = max(0, min($e - $ind, 1));
          }
        }
        $mult = ($easer)($mult);
      }
    }
    // Smoothness implementation.
    // The $smoothness represents a reduced range of the original [0; 1] range.
    // if $smoothness is 25%, the new range will be [0.375; 0.625]
    // Steps are:
    // - find the lower value of the new range ($threshold)
    // - if $multiplier is smaller than that value, floor it to 0
    // - if it is larger,
    //     - subtract the $threshold
    //     - divide it by the $smoothness ($this will return the range to [0; 1])
    // Note: If it doesn't work on some scenarios, consider applying it before the easer.
    if ($this->sm['v'] !== 100) {
      $smoothness = $this->sm['v'] * 0.01;

      if ($smoothness === 0) {
        $smoothness = 0.00000001;
      }
      $threshold = 0.5 - $smoothness * 0.5;

      if ($mult < $threshold) {
        $mult = 0;
      } else {
        $mult = ($mult - $threshold) / $smoothness;
        if ($mult > 1) {
          $mult = 1;
        }
      }
    }

    return $mult * $this->a['v'];
  }

  public function getTextSelectorProp(
    array $_elem, array $_data, array $_arr
  ) {
    throw new \Error('Method not implemented');
  }

  public function getValue($newCharsFlag = false) {
    $this->iterateDynamicProperties();
    $this->_mdf = $newCharsFlag || $this->_mdf;
    $this->_currentTextLength = count($this->elem['textProperty']['currentData']['l'] ?? []) || 0;
    if ($newCharsFlag && $this->data['r'] === 2 && $this->e['v']) {
      $this->e['v'] = $this->_currentTextLength;
    }
    $divisor = $this->data['r'] === 2 ? 1 : 100 / $this->data['totalChars'];
    $o = $this->o['v'] / $divisor;
    $s = ($this->s['v'] ?? 0) / $divisor + $o;
    $e = $this->e['v'] / $divisor + $o;

    if ($s > $e) {
      $_s = $s;

      $s = $e;
      $e = $_s;
    }
    $this->finalS = $s;
    $this->finalE = $e;

    return 0;
  }
}
