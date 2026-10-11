<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableDOMElement;
use LottiePHP\Utils\Enums\ShapeType;
use LottiePHP\Utils\Helpers\GlobalData;
use LottiePHP\Utils\Matrix;
use LottiePHP\Utils\Text\LetterProps;
use LottiePHP\Utils\Text\TextAnimatorProperty;
use LottiePHP\Utils\Text\TextProperty;

use function LottiePHP\Utils\buildShapeString;

abstract class TextElement extends RenderableDOMElement
{
    public ?LetterProps $emptyProp;
    public ?bool $lettersChangedFlag;
    public ?TextAnimatorProperty $textAnimator;
    public ?TextProperty $textProperty;

    public function applyTextPropertiesToMatrix(
        array $documentData,
        Matrix $matrixHelper,
        int $lineNumber,
        int $xPos,
        int $yPos
    ) {
        if ($documentData['ps']) {
            $matrixHelper->translate(
                $documentData['ps'][0],
                $documentData['ps'][1] + (int) $documentData['ascent'],
                0
            );
        }
        $matrixHelper->translate(
            0,
            -(int) $documentData['ls'],
            0
        );

        switch ($documentData['j']) {
            case 1:
                $matrixHelper->translate(
                    (float) $documentData['justifyOffset']
          + ((float) $documentData['boxWidth']
                    - (float) $documentData['lineWidths'][$lineNumber]),
                    0,
                    0
                );

                break;

            case 2:
                $matrixHelper->translate(
                    (float) $documentData['justifyOffset']
          + ((float) $documentData['boxWidth']
                    - (float) $documentData['lineWidths'][$lineNumber])
                    / 2,
                    0,
                    0
                );

                break;

            default:
                break;
        }
        $matrixHelper->translate(
            $xPos,
            $yPos,
            0
        );
    }

    public function buildColor(array $colorData)
    {
        $r = round($colorData[0] * 255);
        $g = round($colorData[1] * 255);
        $b = round($colorData[2] * 255);

        return "rgb({$r},{$g},{$b})";
    }

    public function buildNewText()
    {
        throw new \Error(get_class(${$this}).': Method buildNewText is not implemented');
    }

    public function canResizeFont(bool $_canResize)
    {
        $this->textProperty?->canResizeFont($_canResize);
    }

    public function createPathShape(Matrix $matrixHelper, array $shapes)
    {
        $pathNodes = null;
        $shapeStr = '';
        $length = count($shapes);

        for ($i = 0; $i < $length; ++$i) {
            if (($shapes[$i]['ty'] ?? '') !== ShapeType::Path || !($shapes[$i]['ks']['k'] ?? null)) {
                continue;
            }
            $pathNodes = $shapes[$i]['ks']['k'] ?? null;
            $shapeStr .= buildShapeString(
                $pathNodes,
                count($pathNodes['i']),
                true,
                $matrixHelper
            );
        }

        return $shapeStr;
    }

    public function initElement(
        array $data,
        GlobalData $globalData,
        BaseElement $comp
    ) {
        if (!$data['t']) {
            throw new \Error(get_class(${$this}).": data.t (LottieLayer -> TextData) can't be undefined");
        }
        $this->emptyProp = new LetterProps();
        $this->lettersChangedFlag = true;
        $this->initFrame();
        $this->initBaseData(
            $data,
            $globalData,
            $comp
        );
        $this->textProperty = new TextProperty($this, $data['t']);
        $this->textAnimator = new TextAnimatorProperty($data['t'], $this);
        $this->initTransform();
        $this->initHierarchy();
        $this->initRenderable();
        $this->initRendererElement();
        $this->createContainerElements();
        $this->createRenderableComponents();
        $this->createContent();
        $this->hide();
        $this->textAnimator->searchProperties($this->dynamicProperties);
    }

    public function prepareFrame(float $num)
    {
        $this->_mdf = false;
        $this->prepareRenderableFrame($num);
        $this->prepareProperties($num, $this->isInRange);
    }

    public function setMinimumFontSize(float $_fontSize)
    {
        $this->textProperty?->setMinimumFontSize($_fontSize);
    }

    public function updateDocumentData(array $newData, int $index)
    {
        $this->textProperty?->updateDocumentData($newData, $index);
    }

    public function validateText()
    {
        if (!$this->textProperty?->_mdf && !$this->textProperty?->_isFirstFrame) {
            return;
        }
        $this->buildNewText();
        $this->textProperty->_isFirstFrame = false;
        $this->textProperty->_mdf = false;
    }
}
