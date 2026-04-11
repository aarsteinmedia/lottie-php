<?php

namespace LottiePHP;

use LottiePHP\Animation\AnimationManager;

class LottieToSVG {
  public static function loadAnimation(array $params) {
    return AnimationManager::loadAnimation($params);
  }
}