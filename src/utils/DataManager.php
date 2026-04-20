<?php

namespace LottiePHP\Utils;

class DataManager
{
    public static function completeAnimation(
        array $animation,
        callable $onComplete
    ) {
        // For still rendering we can treat the provided JSON as "complete"
        // and just normalize a few optional fields expected by the renderer.
        if (!isset($animation['assets']) || !is_array($animation['assets'])) {
            $animation['assets'] = [];
        }
        if (!isset($animation['layers']) || !is_array($animation['layers'])) {
            $animation['layers'] = [];
        }
        if (!isset($animation['fonts']) || !is_array($animation['fonts'])) {
            $animation['fonts'] = [];
        }
        if (!isset($animation['chars']) || !is_array($animation['chars'])) {
            $animation['chars'] = [];
        }

        $onComplete($animation);
    }

    public static function loadAnimation(string $path, callable $onComplete)
    {
        $raw = @file_get_contents($path);
        if (!is_string($raw) || '' === $raw) {
            throw new \Error("Could not read animation data from: {$path}");
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new \Error("Invalid JSON animation data: {$path}");
        }

        self::completeAnimation($json, $onComplete);
    }
}
