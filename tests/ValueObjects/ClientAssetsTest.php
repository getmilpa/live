<?php

/**
 * This file is part of Milpa Live — the render-target-agnostic live component core of the Milpa PHP framework.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\Tests\ValueObjects;

use Milpa\Live\ValueObjects\ClientAssets;
use Milpa\Live\ValueObjects\RenderResult;
use PHPUnit\Framework\TestCase;

/**
 * The declared-view contract's asset value (greenhouse decisions/0211): merging deduplicates by URL and keeps
 * first-seen order, so two components sharing a module make the host load it once.
 */
final class ClientAssetsTest extends TestCase
{
    public function testEmptyHasNothingToEmit(): void
    {
        $assets = ClientAssets::empty();

        self::assertTrue($assets->isEmpty());
        self::assertSame(['scripts' => [], 'styles' => []], $assets->toArray());
    }

    public function testMergeDeduplicatesByUrlAndKeepsFirstSeenOrder(): void
    {
        $a = new ClientAssets(scripts: ['/plugins/a/a.js', '/shared/vendor.js'], styles: ['/plugins/a/a.css']);
        $b = new ClientAssets(scripts: ['/shared/vendor.js', '/plugins/b/b.js'], styles: ['/plugins/b/b.css', '/plugins/a/a.css']);

        $merged = $a->merge($b);

        self::assertSame(['/plugins/a/a.js', '/shared/vendor.js', '/plugins/b/b.js'], $merged->scripts);
        self::assertSame(['/plugins/a/a.css', '/plugins/b/b.css'], $merged->styles);
        self::assertFalse($merged->isEmpty());
        // Immutable: neither operand moved.
        self::assertSame(['/plugins/a/a.js', '/shared/vendor.js'], $a->scripts);
        self::assertSame(['/shared/vendor.js', '/plugins/b/b.js'], $b->scripts);
    }

    public function testWithAppendsAndDeduplicatesWithinOneCall(): void
    {
        $assets = ClientAssets::empty()->with(scripts: ['/x.js', '/x.js', '/y.js'], styles: ['/x.css']);

        self::assertSame(['/x.js', '/y.js'], $assets->scripts);
        self::assertSame(['/x.css'], $assets->styles);
        self::assertSame(['scripts' => ['/x.js', '/y.js'], 'styles' => ['/x.css']], $assets->toArray());
    }

    public function testTheConstructorAlreadyDeduplicates(): void
    {
        $assets = new ClientAssets(scripts: ['/a.js', '/b.js', '/a.js'], styles: ['/a.css', '/a.css']);

        self::assertSame(['/a.js', '/b.js'], $assets->scripts);
        self::assertSame(['/a.css'], $assets->styles);
    }

    public function testMergingTheSameAssetsTwiceIsIdempotent(): void
    {
        $assets = new ClientAssets(scripts: ['/a.js'], styles: ['/a.css']);

        self::assertSame($assets->toArray(), $assets->merge($assets)->toArray());
    }

    public function testARenderResultWithoutDeclaredAssetsAnswersEmptyAndKeepsTheLegacyBag(): void
    {
        $result = new RenderResult(output: '<p/>', assets: ['script' => '/milpa-live.js', 'hint' => 'q quits']);

        self::assertTrue($result->clientAssets()->isEmpty(), 'no declaring renderer → empty, never null');
        self::assertSame(['script' => '/milpa-live.js', 'hint' => 'q quits'], $result->assets, 'the legacy string-keyed bag is untouched');
    }

    public function testARenderResultCarriesTheDeclaredAssetsItWasGiven(): void
    {
        $declared = new ClientAssets(scripts: ['/plugins/a/a.js'], styles: ['/plugins/a/a.css']);
        $result = new RenderResult(output: '<p/>', assets: ['script' => '/milpa-live.js'], clientAssets: $declared);

        self::assertSame($declared, $result->clientAssets());
        self::assertSame(['script' => '/milpa-live.js'], $result->assets, 'the typed channel does not rewrite the legacy one');
    }
}
