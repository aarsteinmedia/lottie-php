<?php

namespace LottiePHP\Animation;

class AnimationManager
{
    private static array $registeredAnimations = [];

    /**
     * @param array{
     *  path: null|string,
     *  assetsPath: null|string
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
