<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;
use PHPUnit\Framework\TestCase;

class LottieToSVGTest extends TestCase {
  public function test(): void {
    $generatedSVG = LottieToSVG::renderFirstFrame(dirname(__DIR__, 1) . '/assets/1729.json');

    $targetSVG = file_get_contents(dirname(__DIR__, 1) . '/assets/1729.svg');

    $this->assertEquals($targetSVG, $generatedSVG);
  }
}