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

use Milpa\Live\Components\Dashboard\MetricCardComponent;
use Milpa\Live\Rendering\ViewModel\DashboardViewModelFields;
use Milpa\Live\ValueObjects\ComponentContext;
use PHPUnit\Framework\TestCase;

/**
 * Converted from `tests/smoke.php` lines ~1226-1254 — the view-model
 * fallback-order regression test. The lab's version drives this through
 * *both* `DashboardHtmlRenderer` and `TuiComponentRenderer` (neither of
 * which lives in this package: HTML is `milpa/live-web`, TUI stays in the
 * lab); per the fase-B partition §5 that becomes "two tests post-split".
 * This is `milpa/live`'s half: it proves the actual seam —
 * {@see DashboardViewModelFields} itself — resolves meta over stale
 * request props directly, independent of either renderer. `milpa/live-web`
 * and the lab's Tui suite each get their own renderer-level version of this
 * same regression.
 */
final class DashboardViewModelFieldsTest extends TestCase
{
    public function testStringPrefersMetaOverStaleRequestProps(): void
    {
        $metric = new MetricCardComponent();
        $context = new ComponentContext('metric-viewmodel-check', route: '/lab/dashboard');
        $state = $metric->mount([
            'title' => 'Mounted Title',
            'value' => '42',
            'caption' => 'Mounted caption',
        ], $context);

        $staleProps = ['title' => 'Stale Request Title', 'value' => '42', 'caption' => 'Stale caption'];

        $title = DashboardViewModelFields::string($state->meta, $staleProps, 'title');

        self::assertSame('Mounted Title', $title, 'Expected mounted state title to win over stale request props.');
        self::assertNotSame('Stale Request Title', $title);
    }

    public function testStringFallsBackToPropsWhenMetaKeyIsAbsent(): void
    {
        $value = DashboardViewModelFields::string([], ['title' => 'From Props'], 'title');

        self::assertSame('From Props', $value);
    }

    public function testStringFallsBackToDefaultWhenNeitherMetaNorPropsHaveTheKey(): void
    {
        $value = DashboardViewModelFields::string([], [], 'title', 'Untitled');

        self::assertSame('Untitled', $value);
    }

    public function testBoolPrefersMetaOverProps(): void
    {
        self::assertTrue(DashboardViewModelFields::bool(['flag' => true], ['flag' => false], 'flag'));
        self::assertFalse(DashboardViewModelFields::bool([], ['flag' => false], 'flag', true));
    }

    public function testListPrefersMetaOverPropsAndDropsNonArrayEntries(): void
    {
        $meta = ['items' => [['a' => 1], 'not-an-array', ['b' => 2]]];

        $list = DashboardViewModelFields::list($meta, ['items' => [['ignored' => true]]], 'items');

        self::assertSame([['a' => 1], ['b' => 2]], $list);
    }

    public function testListDefaultsToAnEmptyArray(): void
    {
        self::assertSame([], DashboardViewModelFields::list([], [], 'items'));
    }
}
