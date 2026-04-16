<?php

namespace LottiePHP\Utils\Shapes\Properties;


class ShapeProperty extends ShapeBaseProperty {
  ix?: number;
  pathsData?: ShapePath[] | ShapePath;
  shape?: {
    _mdf?: boolean
    paths?: undefined | {
      shapes: ShapePath[]
      _length: number
    }
  };
  totalShapeLength?: number;
  x?: boolean;
  constructor(
    elem: SVGShapeElement | CVShapeElement | HShapeElement, data: Shape, type: number
  ) {
    super();
    this.propType = PropType.Shape;
    this.comp = elem.comp;
    this.container = elem as ElementInterfaceIntersect;
    this.elem = elem;
    this.data = data;
    this.k = false;
    this.kf = false;
    this._mdf = false;
    const pathData = type === 3 ? data.pt?.k : data.ks?.k;

    if (!pathData) {
      throw new Error(`${this.constructor.name}: Could now get Path Data`);
    }
    this.v = clone(pathData as ShapePath);
    this.pv = clone(this.v);
    this.localShapeCollection = newShapeCollection();
    this.paths = this.localShapeCollection;
    this.paths.addShape(this.v);
    this.effectsSequence = [];
    this.getValue = this.processEffectsSequence;
  }
}

export class KeyframedShapeProperty extends ShapeBaseProperty {
  public lastFrame = initialDefaultFrame;
  constructor(
    elem: SVGShapeElement | CVShapeElement | HShapeElement, data: Shape, type: number
  ) {
    super();
    this.data = data;
    this.propType = PropType.Shape;
    this.comp = elem.comp;
    this.elem = elem;
    this.container = elem as ElementInterfaceIntersect;
    this.offsetTime = elem.data?.st || 0;
    this.keyframes = (type === 3
      ? data.pt?.k
      : data.ks?.k ?? []) as unknown as Keyframe[];
    this.keyframesMetadata = [];
    this.k = true;
    this.kf = true;
    const shapePaths = this.keyframes[0]?.s as ShapePath[] | undefined;
    const { length } = shapePaths?.[0]?.i ?? [];

    this.v = newElement() as ShapePath;
    this.v.setPathData(Boolean(shapePaths?.[0]?.c), length);
    this.pv = clone(this.v);
    this.localShapeCollection = newShapeCollection();
    this.paths = this.localShapeCollection;
    this.paths.addShape(this.v);
    this._caching = {
      lastFrame: initialDefaultFrame,
      lastIndex: 0
    } as Caching;

    this.effectsSequence = [this.interpolateShapeCurrentTime.bind(this)];
    this.getValue = this.processEffectsSequence;
  }
}
