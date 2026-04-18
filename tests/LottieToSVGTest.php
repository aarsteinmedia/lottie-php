<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;
use PHPUnit\Framework\TestCase;

class LottieToSVGTest extends TestCase {
  public function test(): void {
    $output = LottieToSVG::loadAnimation([

    ]);
    var_dump($output);
  }
}