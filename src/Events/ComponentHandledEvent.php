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

use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;

/**
 * POST event: an action finished being applied, whether the component's
 * own `handle()` ran or a `component.handling` listener intercepted it.
 * Dispatched as `component.handled` — pure notification, readonly, no
 * slot.
 *
 * **Short-circuit visibility invariant.** When a `component.handling`
 * listener short-circuits via `InterceptionSlot::shortCircuit()` (or vetoes
 * via `stop()`), the component's own `handle()` never runs — but this
 * event MUST still fire, with {@see $intercepted} `true`, so audit/metrics
 * listeners are never blind to an intercepted interaction.
 */
final readonly class ComponentHandledEvent
{
    /**
     * @param InteractionRequest $request     The request that was handled.
     * @param InteractionResult  $result      The outcome — from the component's own `handle()`,
     *                                        or from a `component.handling` short-circuit/veto.
     * @param bool               $intercepted True if a `component.handling` listener supplied or
     *                                        vetoed {@see $result} (the component's own `handle()`
     *                                        never ran).
     */
    public function __construct(
        public InteractionRequest $request,
        public InteractionResult $result,
        public bool $intercepted = false,
    ) {
    }
}
