<?php

namespace LottiePHP\Animation;

class AnimationManager
{
    /**
     * @param array{
     *  path: null|string,
     *  assetsPath: null|string,
     *  animationData: null|array,
     *  rendererSettings?: array
     * } $params
     */
    public static function loadAnimation(array $params)
    {
        try {
            $animItem = new AnimationItem();
            $animItem->setParams($params);

            return $animItem;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
