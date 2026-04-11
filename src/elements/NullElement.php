<?php

namespace LottiePHP\Elements;

class NullElement /*extends FrameElement*/ {
  public function __constructor(
    array $data,
    array $globalData,
    array $comp
  ) {
    $this->initFrame();
    $this->initBaseData(
      $data, $globalData, $comp
    );
    $this->initTransform();
    $this->initHierarchy();
  }

  public function getBaseElement() {
    return null;
  }

  public function prepareFrame(int $num) {
    $this->prepareProperties($num, true);
  }

  public function renderFrame(?int $_frame) {
    return null;
  }
}
