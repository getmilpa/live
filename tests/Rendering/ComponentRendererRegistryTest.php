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

namespace Milpa\Live\Tests\Rendering;

use Milpa\Live\Rendering\ComponentRendererRegistry;
use Milpa\Live\Tests\Fixtures\FixtureComponentRenderer;
use Milpa\Live\ValueObjects\RenderTarget;
use PHPUnit\Framework\TestCase;

/**
 * Converted from `tests/smoke.php` line ~420 (`ComponentRendererRegistry`'s
 * own construction/resolution) — per the fase-B partition §5, this is "the
 * one `milpa/live`-core-relevant assertion" in the Tui-heavy smoke span it
 * sat in, so it gets its own dedicated core test rather than porting the
 * surrounding TUI machinery (which stays in the lab).
 *
 * Uses {@see FixtureComponentRenderer} instead of the lab's real
 * `AutocompleteHtmlRenderer`/`TuiComponentRenderer` (both `milpa/live-web`
 * or lab-Tui concerns) — only `supportsTarget()` dispatch is under test
 * here, which the fixture's HTML-only implementation exercises just as well.
 */
final class ComponentRendererRegistryTest extends TestCase
{
    public function testResolvingAgainstAnEmptyRegistryReturnsNull(): void
    {
        $registry = new ComponentRendererRegistry();

        self::assertNull($registry->resolve(RenderTarget::HTML));
    }

    public function testResolveDispatchesToTheMostRecentlyRegisteredMatchingRenderer(): void
    {
        $registry = new ComponentRendererRegistry();
        $htmlRenderer = new FixtureComponentRenderer();

        $registry->register($htmlRenderer);

        self::assertSame($htmlRenderer, $registry->resolve(RenderTarget::HTML));
        self::assertNull($registry->resolve(RenderTarget::TUI), 'Expected the registry to resolve nothing for a target no renderer supports.');
        self::assertNull($registry->resolve(RenderTarget::ANSI));
    }
}
