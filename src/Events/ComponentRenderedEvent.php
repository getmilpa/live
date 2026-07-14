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

namespace Milpa\Live\Events;

use Milpa\Live\ValueObjects\RenderRequest;
use Milpa\Live\ValueObjects\RenderResult;

/**
 * POST event: a component finished rendering, whether the renderer's own
 * logic ran or a `component.rendering` listener short-circuited/decorated
 * it. Dispatched as `component.rendered` — pure notification, readonly, no
 * slot.
 *
 * **Short-circuit visibility invariant.** Mirrors {@see ComponentHandledEvent}:
 * an intercepted render MUST still fire this event, with
 * {@see $intercepted} `true`.
 */
final readonly class ComponentRenderedEvent
{
    /**
     * @param string        $componentName The component contract's registered name.
     * @param RenderRequest $request       The render request that was fulfilled.
     * @param RenderResult  $result        The final output — the renderer's own, or a
     *                                     `component.rendering` listener's decorated replacement.
     * @param bool          $intercepted   True if a `component.rendering` listener supplied or
     *                                     vetoed {@see $result} (the renderer's own logic never ran).
     */
    public function __construct(
        public string $componentName,
        public RenderRequest $request,
        public RenderResult $result,
        public bool $intercepted = false,
    ) {
    }
}
