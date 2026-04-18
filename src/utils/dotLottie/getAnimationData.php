<?php

namespace LottiePHP\Utils\DotLottie;

function getAnimationData($input) {
  try {
    if (!$input || gettype($input) !== 'string' && gettype($input) !== 'object') {
      throw new \Error('Broken file or invalid file format');
    }

    if (gettype($input) !== 'string') {
      $animations = is_array($input) ? $input : [$input];

      return [
        'animations' => $animations,
        'isDotLottie' => false,
        'manifest' => null,
      ];
    }

    $file = file_get_contents($input);

    /**
     * Check if file is JSON, first by parsing headers for content-type,
     * than by parsing filename, then – if filename has no extension – by
     * cloning the response and parsing response for content.
     */
    $isJSON = true;
    $finfo = finfo_open(FILEINFO_MIME_TYPE); 
    $mimeType = finfo_file($finfo, $input);

    if ($mimeType === 'application/zip+dotlottie') {
      $isJSON = false;
    }

    if ($isJSON) {
      $ext = pathinfo($input, PATHINFO_EXTENSION);

      if ($ext === 'json') {
        $lottie = $file;

        return [
          'animations' => [$lottie],
          'isDotLottie' => false,
          'manifest' => null,
        ];
      }

      try {
        $lottie = json_decode($file);

        return [
          'animations' => [$lottie],
          'isDotLottie' => false,
          'manifest' => null,
        ];

      } catch (\Exception $error) {
        /* empty */
      }
    }

    [ 'data' => $data, 'manifest' => $manifest ] = getLottieJSON($file);

    return [
      'animations' => $data,
      'isDotLottie' => true,
      'manifest' => $manifest,
    ];
  } catch (\Exception $error) {
    error_log($error->getMessage());

    return [
      'animations' => null,
      'isDotLottie' => false,
      'manifest' => null,
    ];
  }
}