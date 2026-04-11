<?php

namespace LottiePHP\Animation;

use LottiePHP\Animation\AnimationItem;

class AnimationManager
{
    private static array $registeredAnimations = [];

    /**
     * @param array{
     *  path: string|null,
     *  assetsPath: string|null
     * } $params
     */
    public static function loadAnimation(array $params)
    {
        try {
            $animItem = new AnimationItem();
            self::setupAnimation($animItem);
            $animItem->setParams($params);

            return $animItem;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    public static function setupAnimation(AnimationItem $animationItem)
    {
        self::$registeredAnimations[] = ['animation' => $animationItem];
    }
}
