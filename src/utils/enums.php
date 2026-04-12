<?php

namespace LottiePHP\Utils\Enums;

enum ArrayType: string {
  case Float32 = 'float32';
  case Int16 = 'int16';
  case Int32 = 'int32';
  case Uint8 = 'uint8';
  case Uint8c = 'uint8c';
}

enum Modifier: string  {
  case MouseModifier = 'ms';
  case OffsetPathModifier = 'op';
  case PuckerAndBloatModifier = 'pb';
  case RepeaterModifier = 'rp';
  case RoundCornersModifier = 'rd';
  case TrimModifier = 'tm';
  case ZigZagModifier = 'zz';
}

enum PlayMode: string  {
  case Bounce = 'bounce';
  case Normal = 'normal';
}

enum PlayerEvents: string  {
  case Complete = 'complete';
  case Destroyed = 'destroyed';
  case Error = 'error';
  case Frame = 'frame';
  case Freeze = 'freeze';
  case Load = 'load';
  case Loop = 'loop';
  case Next = 'next';
  case Pause = 'pause';
  case Play = 'play';
  case Previous = 'previous';
  case Ready = 'ready';
  case Rendered = 'rendered';
  case Stop = 'stop';
}

enum PreserveAspectRatio: string  {
  case Contain = 'xMidYMid meet';
  case Cover = 'xMidYMid slice';
  case Initial = 'none';
  case None = 'xMinYMin slice';
}

enum RendererType: string  {
  case Canvas = 'canvas';
  case HTML = 'html';
  case SVG = 'svg';
}

enum ShapeType: string  {
  case Ellipse = 'el';
  case Fill = 'fl';
  case GradientFill = 'gf';
  case GradientStroke = 'gs';
  case Group = 'gr';
  case Merge = 'mm';
  case NoStyle = 'no';
  case OffsetPath = 'op';
  case Path = 'sh';
  case PolygonStar = 'sr';
  case PuckerBloat = 'pb';
  case Rectangle = 'rc';
  case Repeater = 'rp';
  case RoundedCorners = 'rd';
  case Stroke = 'st';
  case Transform = 'tr';
  case Trim = 'tm';
  case Twist = 'tw';
  case Unknown = 'ms';
  case ZigZag = 'zz';
}

enum EffectTypes: string  {
  case TransformEffect = 'transformEffect';
}

enum PropType: string  {
  case MultiDimensional = 'multidimensiional';
  case Shape = 'shape';
  case TextSelector = 'textSelector';
  case Transform = 'transform';
  case UniDimensional = 'unidimensional';
}

$lineCapEnum = (object) [
  1 => 'butt',
  2 => 'round',
  3 => 'square'
];

$lineJoinEnum = (object) [
  1 => 'miter',
  2 => 'round',
  3 => 'bevel'
];
