<?php

namespace LottiePHP\Utils\Shapes;

use function LottiePHP\Utils\createSizedArray;
use function LottiePHP\Utils\Pooling\pointPool;

class ShapePath
{
    public $_length = 0;
    public $_maxLength = 8;
    public $c = false;
    public ?array $e;
    public ?array $i;
    public array $lengths = [];
    public ?array $o;
    public ?array $s;
    public ?array $v;

    private $_pointPool;

    public function __get(string $name)
    {
        if ($name === '_type') {

            return 'ShapePath';

        }
    }

    public function __construct()
    {
        $this->v = createSizedArray($this->_maxLength);
        $this->o = createSizedArray($this->_maxLength);
        $this->i = createSizedArray($this->_maxLength);

        $this->_pointPool = pointPool();
    }

    public function doubleArrayLength()
    {
        $this->v = [...$this->v, ...createSizedArray($this->_maxLength)];
        $this->i = [...$this->i, ...createSizedArray($this->_maxLength)];
        $this->o = [...$this->o, ...createSizedArray($this->_maxLength)];
        $this->_maxLength *= 2;
    }

    public function length()
    {
        return $this->_length;
    }

    public function setLength(int $len)
    {
        while ($this->_maxLength < $len) {
            $this->doubleArrayLength();
        }
        $this->_length = $len;
    }

    public function setPathData(bool $closed, int $len)
    {
        $this->c = $closed;
        $this->setLength($len);
        $i = 0;

        while ($i < $len) {
            $this->v[$i] = $this->_pointPool->newElement();
            $this->o[$i] = $this->_pointPool->newElement();
            $this->i[$i] = $this->_pointPool->newElement();
            $i++;
        }
    }

    public function setTripleAt(
        int $vX,
        int $vY,
        int $oX,
        int $oY,
        int $iX,
        int $iY,
        int $pos,
        ?bool $replace = null
    ) {
        $this->setXYAt(
            $vX,
            $vY,
            'v',
            $pos,
            $replace
        );
        $this->setXYAt(
            $oX,
            $oY,
            'o',
            $pos,
            $replace
        );
        $this->setXYAt(
            $iX,
            $iY,
            'i',
            $pos,
            $replace
        );
    }

    public function setXYAt(
        int $x,
        int $y,
        string $type,
        int $pos,
        ?bool $replace = false
    ) {
        $arr = [];

        $this->_length = max($this->_length, $pos + 1);
        if ($this->_length >= $this->_maxLength) {
            $this->doubleArrayLength();
        }
        switch ($type) {
            case 'v': {
                $arr = $this->v;
                break;
            }
            case 'i': {
                $arr = $this->i;
                break;
            }
            case 'o': {
                $arr = $this->o;
                break;
            }
            default: {
                $arr = [];
                break;
            }
        }
        if (!$arr[$pos] || !$replace) {
            $arr[$pos] = $this->_pointPool->newElement();
        }
        $arr[$pos][0] = $x;
        $arr[$pos][1] = $y;
    }

    public function toReversed()
    {
        $newPath = new ShapePath();

        $newPath->setPathData($this->c, $this->_length);
        $vertices = $this->v;
        $outPoints = $this->o;
        $inPoints = $this->i;

        $init = 0;

        if ($this->c) {
            $newPath->setTripleAt(
                $vertices[0][0] ?? 0,
                $vertices[0][1] ?? 0,
                $inPoints[0][0] ?? 0,
                $inPoints[0][1] ?? 0,
                $outPoints[0][0] ?? 0,
                $outPoints[0][1] ?? 0,
                0,
                false
            );
            $init = 1;
        }
        $_length = $this->_length;
        $cnt = $_length - 1;

        for ($i = $init; $i < $_length; $i++) {
            $newPath->setTripleAt(
                $vertices[$cnt][0] ?? 0,
                $vertices[$cnt][1] ?? 0,
                $inPoints[$cnt][0] ?? 0,
                $inPoints[$cnt][1] ?? 0,
                $outPoints[$cnt][0] ?? 0,
                $outPoints[$cnt][1] ?? 0,
                $i,
                false
            );
            $cnt -= 1;
        }

        return $newPath;
    }
}
