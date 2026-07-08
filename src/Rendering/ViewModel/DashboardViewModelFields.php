<?php

/**
 * This file is part of Milpa Live — the render-target-agnostic live component core of the Milpa PHP framework.
 *
 * (c) TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\Rendering\ViewModel;

/**
 * Single seam for deriving dashboard-primitive identity fields (title,
 * description, brand, items, ...) from mounted state meta and request
 * props. Both {@see \Milpa\Live\Rendering\DashboardHtmlRenderer} and
 * {@see \Milpa\Live\Rendering\TuiComponentRenderer} resolve these fields
 * through here instead of re-deriving their own fallback chain, so the
 * two render targets cannot silently disagree on precedence again --
 * state meta (set at mount time, and possibly mutated since) always wins
 * over the request's current props, which wins over the family default.
 */
final class DashboardViewModelFields
{
    private function __construct()
    {
    }

    /**
     * Resolves a string field, preferring `$meta[$key]`, then
     * `$props[$key]`, then `$default`.
     *
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $props
     */
    public static function string(array $meta, array $props, string $key, string $default = ''): string
    {
        return (string) ($meta[$key] ?? $props[$key] ?? $default);
    }

    /**
     * Resolves a boolean field, preferring `$meta[$key]`, then
     * `$props[$key]`, then `$default`.
     *
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $props
     */
    public static function bool(array $meta, array $props, string $key, bool $default = false): bool
    {
        return (bool) ($meta[$key] ?? $props[$key] ?? $default);
    }

    /**
     * Resolves a list-of-records field, preferring `$meta[$key]`, then
     * `$props[$key]`, defaulting to an empty list. Non-array entries
     * within the resolved value are silently dropped rather than
     * causing an error, since list items are expected to each be an
     * associative array (e.g. a sidebar item, an alert entry).
     *
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $props
     *
     * @return array<int, array<string, mixed>>
     */
    public static function list(array $meta, array $props, string $key): array
    {
        $value = $meta[$key] ?? $props[$key] ?? [];

        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }
}
