<?php

namespace LottiePHP\Utils\DotLottie;

function getAnimationData(mixed $input)
{
    if (!$input || 'string' !== gettype($input) && 'object' !== gettype($input)) {
        throw new \InvalidArgumentException('Broken file or invalid file format');
    }

    if ('string' !== gettype($input)) {
        $animations = is_array($input) ? $input : [$input];

        return [
            'animations' => $animations,
            'isDotLottie' => false,
            'manifest' => null,
        ];
    }

    $file = file_get_contents($input);
    if (false === $file) {
        throw new \InvalidArgumentException('Could not read file: '.$input);
    }

    $isJSON = !str_starts_with($file, 'PK\x03\x04');

    if ($isJSON) {
        $ext = pathinfo($input, PATHINFO_EXTENSION);

        if ('json' === $ext) {
            return [
                'animations' => [$file],
                'isDotLottie' => false,
                'manifest' => null,
            ];
        }

        $decoded = json_decode($file, true);
        if (is_array($decoded)) {
            return [
                'animations' => [$decoded],
                'isDotLottie' => false,
                'manifest' => null,
            ];
        }
    }

    ['data' => $data, 'manifest' => $manifest] = getLottieJSON($file);

    unset($file);

    return [
        'animations' => $data ?? [],
        'isDotLottie' => true,
        'manifest' => $manifest,
    ];
}
