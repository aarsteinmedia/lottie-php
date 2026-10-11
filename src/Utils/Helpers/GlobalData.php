<?php

namespace LottiePHP\Utils\Helpers;

/**
 * Render state shared by the renderer and every element (frameNum, frameId, _mdf, defs, ...).
 *
 * In lottie-web this is a plain object, so all elements see the same instance. A PHP array would
 * be copied into each element, leaving them stuck on the frame they were created at. Extending
 * ArrayObject keeps the `$globalData['key']` syntax while sharing one instance.
 *
 * Don't override offsetGet(): ArrayObject only supports nested writes such as
 * `$globalData['projectInterface']['currentFrame'] = 1` with its built-in implementation.
 */
final class GlobalData extends \ArrayObject {}
