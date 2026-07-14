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

namespace Milpa\Live\Contracts\Rendering;

use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\ValueObjects\RenderRequest;
use Milpa\Live\ValueObjects\RenderResult;
use Milpa\Live\ValueObjects\RenderTarget;

/**
 * Turns a mounted (or mountable) component into output for one
 * {@see RenderTarget} — HTML, TUI, or ANSI. This is the render-target
 * seam the whole package is built around: a component definition never
 * renders itself, and a renderer never assumes which target it is asked
 * for beyond what {@see supportsTarget()} declares. A single renderer MAY
 * support only a subset of registered components (see each
 * implementation's own allow-list) in addition to declaring which
 * target(s) it supports.
 */
interface ComponentRendererInterface
{
    /**
     * Whether this renderer can produce output for the given target at all
     * (independent of which specific component is being rendered). Used by
     * {@see ComponentRendererRegistryInterface} to dispatch by target
     * instead of renderers being hand-wired per call site.
     */
    public function supportsTarget(RenderTarget $target): bool;

    /**
     * Renders the component for this renderer's target.
     *
     * If {@see RenderRequest::$state} is `null`, implementations MUST
     * mount the component first (via {@see ComponentDefinitionInterface::mount()})
     * using {@see RenderRequest::$props} and {@see RenderRequest::$context}
     * before rendering it, and MUST return that freshly-mounted snapshot
     * as {@see RenderResult::$state}. If `$state` is already provided
     * (e.g. re-rendering after {@see ComponentDefinitionInterface::handle()}),
     * implementations render that snapshot as-is instead of remounting.
     *
     * @throws \InvalidArgumentException If the component's {@see \Milpa\Live\ValueObjects\ComponentContract::$name}
     *                                   is not one this renderer supports.
     */
    public function render(ComponentDefinitionInterface $component, RenderRequest $request): RenderResult;
}
