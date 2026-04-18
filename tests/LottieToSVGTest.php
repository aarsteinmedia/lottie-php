<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;
use PHPUnit\Framework\TestCase;

class LottieToSVGTest extends TestCase {
  public function test(): void {
    $output = LottieToSVG::load(dirname(__DIR__, 1) . '/assets/1000.json');
    var_dump($output);
  }
}