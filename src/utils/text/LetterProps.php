<?php

namespace LottiePHP\Utils\Text;

class LetterProps
{
    public ?bool $__complete;
    public array $_mdf;
    public array|string $fc;
    public int|string $m;
    public ?int $o;
    public int|array $p;
    public string|array|null $sc;
    public ?int $sw;
    public ?string $t;

    public function __construct(
        ?int $o = null,
        ?int $sw = null,
        ?string $sc = null,
        ?string $fc = null,
        null|int|string $m = null,
        null|int|array $p = null
    ) {
        $this->o = $o;
        $this->sw = $sw;
        $this->sc = $sc;
        $this->fc = $fc;
        $this->m = $m;
        $this->p = $p;
        $this->_mdf = [
          'fc' => (bool)($fc),
          'm' => true,
          'o' => true,
          'p' => true,
          'sc' => (bool)($sc),
          'sw' => (bool)($sw),
        ];
    }

    public function update(
        int $o,
        int $sw,
        ?string $sc,
        ?string $fc,
        ?string $m,
        ?array $p
    ) {
        $this->_mdf['o'] = false;
        $this->_mdf['sw'] = false;
        $this->_mdf['sc'] = false;
        $this->_mdf['fc'] = false;
        $this->_mdf['m'] = false;
        $this->_mdf['p'] = false;
        $isUpdated = false;

        if ($this->o !== $o) {
            $this->o = $o;
            $this->_mdf['o'] = true;
            $isUpdated = true;
        }
        if ($this->sw !== $sw) {
            $this->sw = $sw;
            $this->_mdf['sw'] = true;
            $isUpdated = true;
        }
        if ($this->sc !== $sc) {
            $this->sc = $sc;
            $this->_mdf['sc'] = true;
            $isUpdated = true;
        }
        if ($this->fc !== $fc) {
            $this->fc = $fc;
            $this->_mdf['fc'] = true;
            $isUpdated = true;
        }
        if ($this->m !== $m) {
            $this->m = $m;
            $this->_mdf['m'] = true;
            $isUpdated = true;
        }
        if (
            $p &&
            count($p) > 0 &&
            (($this->p)[0] !== $p[0] ||
              ($this->p)[1] !== $p[1] ||
              ($this->p)[4] !== $p[4] ||
              ($this->p)[5] !== $p[5] ||
              ($this->p)[12] !== $p[12] ||
              ($this->p)[13] !== $p[13])
        ) {
            $this->p = $p;
            $this->_mdf['p'] = true;
            $isUpdated = true;
        }

        return $isUpdated;
    }
}
