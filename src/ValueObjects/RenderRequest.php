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

namespace Milpa\Live\ValueObjects;

/**
 * The input to {@see \Milpa\Live\Contracts\Rendering\ComponentRendererInterface::render()}.
 * When `$state` is `null`, the renderer MUST mount the component fresh
 * from `$props`/`$context`; when `$state` is given, the renderer renders
 * that snapshot as-is (`$props` is then only consulted as a fallback for
 * values not already captured in state — see e.g.
 * {@see \Milpa\Live\Rendering\ViewModel\DashboardViewModelFields}'s
 * meta-over-props precedence).
 */
final readonly class RenderRequest
{
    /**
     * @param ComponentContext     $context The render-target-agnostic context to mount/render within.
     * @param array<string, mixed> $props   Props to mount with, or to fall back to when `$state` is given.
     * @param StateSnapshot|null   $state   An already-mounted snapshot to render as-is, or `null` to mount fresh.
     * @param RenderTarget         $target  Which target this request is for; renderers MUST reject a request
     *                                      whose target they do not {@see \Milpa\Live\Contracts\Rendering\ComponentRendererInterface::supportsTarget()}.
     * @param array<string, mixed> $options Target-specific rendering options (e.g. TUI's `width`/`focused`/`cursor`).
     */
    public function __construct(
        public ComponentContext $context,
        public array $props = [],
        public ?StateSnapshot $state = null,
        public RenderTarget $target = RenderTarget::HTML,
        public array $options = [],
    ) {
    }
}
