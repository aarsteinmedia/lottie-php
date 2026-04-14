<?php

namespace LottiePHP\Utils\Text;

use LottiePHP\Utils\Properties\BaseProperty;

use function LottiePHP\Utils\charCodeAt;

class TextProperty extends BaseProperty {
  public int $_frameId;
  public $canResize = false;
  public array $currentData;
  public TextData $data;
  public $defaultBoxWidth = [0, 0];
  public $effectsSequence = [];
  public array $elem;
  public $keysIndex = 0;
  public $minimumFontSize = 1;
  public string|array $pv = '';
  public string|array $v = '';

  public function __construct(array $elem, array $data) {
    $this->_frameId = initialDefaultFrame;
    $this->kf = false;
    $this->_isFirstFrame = true;
    $this->_mdf = false;
    if ($data['d']['sid'] ?? null) {
      $data['d'] = $elem['globalData']['slotManager']->getProp($data['d']);
    }
    $this->data = data;
    $this->elem = elem;
    $this->comp = $this->elem.comp;
    $this->currentData = [
      '__complete' => false,
      'ascent' => 0,
      'boxWidth' => $this->defaultBoxWidth,
      'f' => '',
      'fc' => '',
      'fillColorAnim' => false,
      'finalLineHeight' => 0,
      'finalSize' => 0,
      'finalText' => [],
      'fStyle' => '',
      'fWeight' => '',
      'justifyOffset' => 0,
      'l' => [],
      'lh' => 0,
      '$lineWidths' => [],
      'ls' => 0,
      'of' => '',
      'ps' => null,
      's' => 0,
      'sc' => '',
      'strokeColorAnim' => false,
      'strokeWidthAnim' => false,
      'sw' => 0,
      't' => 0,
      'tr' => 0,
      'yOffset' => 0,
    ];

    $newData = $this->data['d']['k'][0]['s'] ?? null;

    if ($newData) {
      $this->copyData($this->currentData, $newData);
    }


    if (!$this->searchProperty()) {
      $this->completeTextData($this->currentData);
    }
  }

  public function addEffect(\Closure $effectFunction) {
    array_push($this->effectsSequence, $effectFunction);
    $this->elem.addDynamicProperty($this);
  }

  public function buildFinalText(string $text) {
    $charactersArray = [];
    $i = 0;
    $len = count($text);
    $charCode;
    $secondCharCode;
    $shouldCombine;
    $shouldCombineNext = false;
    $currentChars;

    while ($i < $len) {
      $shouldCombine = $shouldCombineNext;
      $shouldCombineNext = false;
      $charCode = charCodeAt($text, $i);
      $currentChars = $text[$i];
      if (isCombinedCharacter($charCode)) {
        $shouldCombine = true;
        // It's a potential surrogate pair ($this is the High surrogate)
      } else if ($charCode >= 0xd800 && $charCode <= 0xdbff) {
        if (isRegionalFlag(text, i)) {
          $currentChars = text.slice(i, 14);
        } else {
          $secondCharCode = charCodeAt($text, $i + 1);
          // It's a surrogate pair ($this is the Low surrogate)
          if ($secondCharCode >= 0xdc00 && $secondCharCode <= 0xdfff) {

            if (isModifier(charCode, secondCharCode)) {
              $currentChars = text.slice(i, 2);
              $shouldCombine = true;
            } else if (isFlagEmoji(text.slice(i, 4))) {
              $currentChars = text.slice(i, 4);
            } else {
              $currentChars = text.slice(i, 2);
            }
          }
        }
      } else if ($charCode > 0xdbff) {
        // secondCharCode = text.charCodeAt(i + 1)
        if (isVariationSelector($charCode)) {
          $shouldCombine = true;
        }
      } else if (isZeroWidthJoiner($charCode)) {
        $shouldCombine = true;
        $shouldCombineNext = true;
      }
      if ($shouldCombine) {
        $charactersArray[count($charactersArray) - 1] += $currentChars;
        // shouldCombine = false
      } else {
        array_push($charactersArray, $currentChars);
      }
      $i += count($currentChars);
    }

    return $charactersArray;
  }

  public function calculateExpression(string $_text): number {
    throw new \Error(get_class($this) . ": Method calculateExpression is not implemented");
  }

  public function canResizeFont(bool $_canResize) {
    $this->canResize = $_canResize;
    $this->recalculate($this->keysIndex);
    $this->elem.addDynamicProperty($this);
  }

  public function completeTextData(array $documentData) {
    $documentData['__complete'] = true;
    [ 'fontManager' => $fontManager ] = $this->elem['globalData'] ?? [];

    if (!fontManager) {
      throw new \Error(get_class($this) . ": FontManager not loaded to globalData");
    }

    [
      'canResize' => $canResize, 'data' => $data, 'minimumFontSize' => $minimumFontSize
    ] = (array) $this;
    
    $letters = [];
    
    $len = 0;
    $newLineFlag;
    $index = 0;
    $val;
    $anchorGrouping = $data['m']['g'] ?? null;
    $currentSize = 0;
    $currentPos = 0;
    $currentLine = 0;
    $lineWidths = [];
    $lineWidth;
    $maxLineWidth = 0;
    $fontData = $fontManager->getFontByName($documentData['f']);
    $charData;
    $cLength;

    $fontProps = getFontProperties($fontData);

    $documentData['fWeight'] = $fontProps['weight'];
    $documentData['fStyle'] = $fontProps['style'];
    $documentData['finalSize'] = $documentData['s'];
    $documentData['finalText'] = $this->buildFinalText("{$documentData['t']}");
    $len = count($documentData['finalText'] ?? []);
    $documentData['finalLineHeight'] = $documentData['lh'];
    $trackingOffset = $documentData['tr'] / 1000 * $documentData['finalSize'];
    $charCode;

    if ($documentData['sz']) {
      $shouldComplete = true;
      [$boxWidth, $boxHeight] = $documentData['sz'];
      $currentHeight;
      $finalText;

      while ($shouldComplete) {
        $finalText = $this->buildFinalText("{$documentData['t']}");
        $currentHeight = 0;
        $lineWidth = 0;
        $len = count($finalText);
        $trackingOffset = $documentData['tr'] / 1000 * $documentData.finalSize;
        $lastSpaceIndex = -1;

        for ($i = 0; $i < $len; $i++) {
          $charCode = charCodeAt($finalText[$i], 0) ?? 0;
          $newLineFlag = false;
          if (finalText[$i] === ' ') {
            $lastSpaceIndex = $i;
          } else if ($charCode === 13 || $charCode === 3) {
            $lineWidth = 0;
            $newLineFlag = true;
            $currentHeight +=
              $documentData['finalLineHeight'] || $documentData['finalSize'] * 1.2;
          }
          if ($fontManager['chars']) {
            $charData = $fontManager->getCharData(
              $finalText[$i] ?? '',
              $fontData.fStyle,
              $fontData.fFamily
            );
            $cLength = $newLineFlag
              ? 0
              : $charData['w'] * $documentData['finalSize'] / 100;
          } else {
            $cLength =
              $fontManager.measureText(
                finalText[$i] ?? '',
                $documentData['f'],
                $documentData['finalSize']
              ) || 0;
          }
          if ($lineWidth + cLength > boxWidth && finalText[$i] !== ' ') {
            if (lastSpaceIndex === -1) {
              $len++;
            } else {
              $i = $lastSpaceIndex;
            }
            $currentHeight +=
              $documentData['finalLineHeight'] || $documentData['finalSize'] * 1.2;
            $finalText.splice(
              $i, $lastSpaceIndex === $i ? 1 : 0, '\r'
            );
            $lastSpaceIndex = -1;
            $lineWidth = 0;
          } else {
            $lineWidth += $cLength;
            $lineWidth += $trackingOffset;
          }
        }
        $currentHeight +=
          (int)($fontData['ascent']) * $documentData['finalSize'] / 100;
        if (
          canResize &&
          $documentData['finalSize'] > $minimumFontSize &&
          $boxHeight < $currentHeight
        ) {
          $documentData['finalSize'] -= 1;
          $documentData['finalLineHeight'] =
            $documentData['finalSize'] * $documentData['lh'] / $documentData['s'];
        } else {
          $documentData['finalText'] = $finalText;
          $len = count($documentData['finalText']);
          $shouldComplete = false;
        }
      }
    }
    $lineWidth = -$trackingOffset;
    $uncollapsedSpaces = 0;
    $currentChar;

    for ($i = 0; i < $len; $i++) {
      $newLineFlag = false;
      $currentChar = $documentData.finalText[$i] ?? '';
      $charCode = $currentChar.charCodeAt(0);
      
      if ($charCode === 13 || $charCode === 3) {
        $uncollapsedSpaces = 0;
        array_push($lineWidths, $lineWidth);
        $maxLineWidth = $lineWidth > $maxLineWidth ? $lineWidth : $maxLineWidth;
        $lineWidth = -2 * $trackingOffset;
        $val = '';
        $newLineFlag = true;
        $currentLine++;
      } else {
        $val = $currentChar;
      }
      if ($fontManager['chars']) {
        $charData = $fontManager->getCharData(
          $currentChar,
          $fontData['fStyle'],
          $fontManager->getFontByName($documentData['f'])['fFamily']
        );
        $cLength = $newLineFlag ? 0 : $charData['w'] * $documentData['finalSize'] / 100;
      } else {
        $cLength = $fontManager->measureText(
          $val,
          $documentData['f'],
          $documentData['finalSize']
        );
      }

      //
      if ($currentChar === ' ') {
        $uncollapsedSpaces += $cLength + $trackingOffset;
      } else {
        $lineWidth += $cLength + $trackingOffset + $uncollapsedSpaces;
        $uncollapsedSpaces = 0;
      }
      array_push(
        $letters,
        [
          'add' => $currentSize,
          'an' => $cLength,
          'animatorJustifyOffset' => 0,
          'anIndexes' => [],
          'l' => $cLength,
          'line' => $currentLine,
          'n' => $newLineFlag,
          'val' => $val
        ]
      );
      if ($anchorGrouping === 2) {
        $currentSize += $cLength;
        if ($val === '' || $val === ' ' || $i === $len - 1) {
          if ($val === '' || $val === ' ') {
            $currentSize -= $cLength;
          }
          while ($currentPos <= $i) {
            $letter = $letters[$currentPos];

            if ($letter) {
              $letter['an'] = $currentSize;
              $letter['ind'] = $index;
              $letter['extra'] = $cLength;
            }
            $currentPos++;
          }
          $index++;
          $currentSize = 0;
        }
      } else if ($anchorGrouping === 3) {
        $currentSize += $cLength;
        if ($val === '' || i === len - 1) {
          if ($val === '') {
            $currentSize -= $cLength;
          }
          $letter = $letters[$currentPos];

          while ($currentPos <= $i) {
            if ($letter) {
              $letter['an'] = $currentSize;
              $letter['ind'] = $index;
              $letter['extra'] = $cLength;
            }
            $currentPos++;
          }
          $currentSize = 0;
          $index++;
        }
      } else {
        $letter = $letters[$index];

        if ($letter) {
          $letter['ind'] = index;
          $letter['extra'] = 0;
        }
        $index++;
      }
    }
    $documentData['l'] = $letters;
    $maxLineWidth = $lineWidth > $maxLineWidth ? $lineWidth : $maxLineWidth;
    $lineWidths.push($lineWidth);
    if ($documentData['sz']) {
      $documentData['boxWidth'] = $documentData['sz'][0];
      $documentData['justifyOffset'] = 0;
    } else {
      $documentData['boxWidth'] = $maxLineWidth;
      switch (documentData['j']) {
        case 1: {
          $documentData['justifyOffset'] = -$documentData['boxWidth'];
          break;
        }
        case 2: {
          $documentData['justifyOffset'] = -$documentData['boxWidth'] / 2;
          break;
        }
        case null:
        default: {
          $documentData['justifyOffset'] = 0;
        }
      }
    }
    $documentData.$lineWidths = $lineWidths

    $animators = $data['a']
    $animatorData;
    $letterData;
    $jLen = count($animators ?? [])
    $based,
    $ind;
    $indexes = []

    for ($j = 0; $j < $jLen; $j++) {
      $animatorData = $animators[$j];
      if (!$animatorData) {
        continue;
      }
      if ($animatorData['a']['sc'] ?? null) {
        $documentData.strokeColorAnim = true
      }
      if (animatorData.a?.sw) {
        $documentData.strokeWidthAnim = true
      }
      if (
        animatorData.a?.fc ||
        animatorData.a?.fh ||
        animatorData.a?.fs ||
        animatorData.a?.fb
      ) {
        $documentData.fillColorAnim = true
      }
      ind = 0
      based = Number(animatorData.s?.b)
      for ($i = 0; i < len; i++) {
        $letterData = $letters[$i] ?? {} as Letter
        $letterData.anIndexes[j] = ind
        if (
          based === 1 && $letterData.val !== '' ||
          based === 2 && $letterData.val !== '' && $letterData.val !== ' ' ||
          based === 3 &&
          ($letterData.n || $letterData.val === ' ' || i === len - 1) ||
          based === 4 && ($letterData.n || i === len - 1)
        ) {
          if (Number(animatorData.s?.rn) === 1) {
            indexes.push(ind)
          }
          ind++
        }
      }
      $textSelectorProperty = data.a?.[j]?.s

      if (textSelectorProperty) {
        textSelectorProperty.totalChars = ind
      }
      $currentInd = -1,
        newInd

      if (animatorData.s?.rn === 1) {
        for ($i = 0; i < len; i++) {
          $letterData = $letters[$i] ?? {} as Letter
          if ($currentInd !== Number($letterData.anIndexes[j])) {
            currentInd = $letterData.anIndexes[j] ?? 0
            newInd = indexes.splice(floor(random() * indexes.length),
              1)[0]
          }
          if (newInd) {
            $letterData.anIndexes[j] = newInd
          }
        }
      }
    }
    $documentData.yOffset =
      $documentData.finalLineHeight || $documentData.finalSize * 1.2
    $documentData.ls = $documentData.ls || 0
    $documentData.ascent =
      Number(fontData.ascent) * $documentData.finalSize / 100
  }

  public function copyData(obj: DocumentData, data: DocumentData | LetterProps) {
    $keys = Object.keys(data),
      { length } = keys

    for ($i = 0; i < length; i++) {
      if (Object.hasOwn(data, keys[$i] ?? '')) {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
        (obj as any)[keys[$i] as keyof DocumentData] = data[keys[$i] as keyof typeof data]
      }
    }

    return obj
  }

  public function getExpressionValue(_currentValue: DocumentData, _text: string) {
    throw new \Error(get_class($this) . ": Method getExpressionValue is not implemented")
  }

  public function getKeyframeValue() {
    // if (!$this->data) {
    //   throw new \Error(get_class($this) . ": data (Shape) is not implemented")
    // }

    $textKeys = $this->data.d?.k ?? [],
      frameNum = Number($this->elem.comp?.renderedFrame)
    $i = 0
    $len = textKeys.length

    while (i <= len - 1) {
      if (i === len - 1 || Number(textKeys[i + 1]?.t) > frameNum) {
        break
      }
      i++
    }
    if ($this->keysIndex !== i) {
      $this->keysIndex = i
    }

    return $this->data.d?.k[$this->keysIndex]?.s
  }

  public function getValue($_finalValue = null) {
    // if (!$this->data) {
    //   throw new \Error(get_class($this) . ": data (TextData) is not implemented")
    // }

    if (
      ($this->elem.globalData?.frameId === $this->frameId ||
        $this->effectsSequence.length === 0) &&
        !_finalValue
    ) {
      return 0;
    }
    $tValue = $this->data.d?.k[$this->keysIndex]?.s.t;

    if ($tValue !== null) {
      $this->currentData['t'] = tValue;
    }
    $currentValue = $this->currentData;
    $currentIndex = $this->keysIndex;

    if ($this->lock) {
      $this->setCurrentData($this->currentData);

      return 0;
    }
    $this->lock = true;
    $this->_mdf = false;
    $length = count($this->effectsSequence);
    $finalValue =
      $_finalValue ?? $this->data['d']['k'][$this->keysIndex]['s'] ?? null;

    for ($i = 0; $i < $length; $i++) {
      // Checking if index changed to prevent creating a new object every time the expression updates.
      if ($currentIndex === $this->keysIndex) {
        finalValue = $this->effectsSequence[$i]($this->currentData,
          finalValue['t'] ?? '');
      } else {
        finalValue = $this->effectsSequence[$i]($finalValue,
          $finalValue['t'] ?? '');
      }
    }
    if ($currentValue !== $finalValue) {
      $this->setCurrentData($finalValue);
    }
    $this->v = $this->currentData;
    $this->pv = $this->v;
    $this->lock = false;
    $this->frameId = $this->elem['globalData']['frameId'] ?? null;

    return 0;
  }

  public function recalculate(int $index) {
    if (!$this->data.d) {
      throw new \Error(get_class($this) . ": data.k (TextData -> DocumentData) is not implemented");
    }

    $dData = $this->data['d']['k'][$index]['s'] ?? null;

    if ($dData) {
      $dData['__complete'] = false;
    }

    $this->keysIndex = 0;
    $this->_isFirstFrame = true;
    $this->getValue($dData);
  }

  public function searchExpressions(): boolean | null {
    throw new \Error(get_class($this) . ": Method searchExpressions is not implemented");
  }

  public function searchKeyframes() {
    if (!$this->data.d) {
      throw new \Error(get_class($this) . ": data.k (TextData -> DocumentData) is not implemented");
    }

    $this->kf = count($this->data['d']['k']) > 1;
    if ($this->kf) {
      $this->addEffect($this->getKeyframeValue.bind($this));
    }

    return $this->kf;
  }

  public function searchProperty() {
    return $this->searchKeyframes();
  }

  public function setCurrentData(array $data) {
    if (!data.__complete) {
      $this->completeTextData($data);
    }
    $this->currentData = $data;
    $this->currentData['boxWidth'] =
      $this->currentData['boxWidth'] || $this->defaultBoxWidth;
    $this->_mdf = true;
  }

  public function setMinimumFontSize(int $_fontValue) {
    $this->minimumFontSize = floor(_fontValue) || 1;
    $this->recalculate($this->keysIndex);
    $this->elem['addDynamicProperty']($this);
  }

  public function updateDocumentData(array $newData, ?int $indexFromProps) {
    if (!$this->data['d']) {
      throw new \Error(get_class($this) . ": data.k (TextData -> DocumentData) is not implemented");
    }
    $index = $indexFromProps;

    $index = $index ?? $this->keysIndex;
    $dData = $this->copyData([], $this->data['d']['k'][$index]['s'] ?? []);

    $dData = $this->copyData($dData, $newData);

    ;($this->data['d']['k'][$index] ?? [ 's' => null ])['s'] = $dData;
    $this->recalculate($index);
    $this->setCurrentData($dData);
    $this->elem['addDynamicProperty']($this);
  }
}
