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

namespace Milpa\Live\Events;

use Milpa\Live\ValueObjects\RenderRequest;

/**
 * PRE event: a component is about to be rendered via
 * {@see \Milpa\Live\Contracts\Rendering\ComponentRendererInterface::render()}.
 *
 * Dispatched as `component.rendering`, ALWAYS alongside a
 * {@see \Milpa\Events\InterceptionSlot} — a plugin may short-circuit with a
 * replacement {@see \Milpa\Live\ValueObjects\RenderResult} to decorate or
 * wholly replace the renderer's own output (e.g. injecting a banner,
 * swapping in a cached render for this target). Readonly; the mutable
 * escape hatch lives entirely in the slot dispatched alongside this event.
 * See {@see \Milpa\Live\Events\LiveEventEmitter::withRendering()} for the
 * exact short-circuit/veto/passthrough contract.
 */
final readonly class ComponentRenderingEvent
{
    /**
     * @param string        $componentName The component contract's registered name.
     * @param RenderRequest $request       The render request about to be fulfilled.
     */
    public function __construct(
        public string $componentName,
        public RenderRequest $request,
    ) {
    }
}
