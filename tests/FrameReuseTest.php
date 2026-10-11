<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Rendering several frames from one loaded animation (the batch API) must give the same
 * visible result as loading the file fresh for each frame. Exercises the per-frame update
 * paths (shared frameId, shape modifiers, masks, pools) that a single render never reaches.
 *
 * @internal
 *
 * @coversNothing
 */
final class FrameReuseTest extends TestCase
{
    #[DataProvider('assets')]
    public function testReusedAnimationMatchesFreshRender(string $path): void
    {
        LottieToSVG::setCacheSize(0);

        $anim = LottieToSVG::load($path);
        $last = max(0, (int) $anim->totalFrames - 1);
        $middle = intdiv($last + 1, 2);

        // Two steps forwards (the second releases pooled modifier data from the first),
        // then backwards, so state left by later frames must be undone
        LottieToSVG::renderFrameFromAnimation($anim, 0);
        LottieToSVG::renderFrameFromAnimation($anim, intdiv($last + 1, 4));
        LottieToSVG::renderFrameFromAnimation($anim, $last);
        $reused = LottieToSVG::renderFrameFromAnimation($anim, $middle);
        // Large assets need most of a 128M memory_limit each; load() frees this one first
        unset($anim);

        $fresh = LottieToSVG::renderFrameFromAnimation(LottieToSVG::load($path), $middle);
        // The fresh animation is now unreferenced, but only the cycle collector frees it
        gc_collect_cycles();

        self::assertSame(self::normalizeVisible($fresh), self::normalizeVisible($reused));
    }

    /** @return iterable<string, array{string}> */
    public static function assets(): iterable
    {
        return SnapshotTest::assets();
    }
}
