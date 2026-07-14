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

/**
 * PRE event: a client-originated action is about to be applied via
 * {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface::handle()}.
 *
 * Dispatched as `component.handling`, ALWAYS alongside a
 * {@see \Milpa\Events\InterceptionSlot} in the same payload — the one seam
 * in the component lifecycle where a plugin may legitimately answer on the
 * component's behalf (e.g. a cache replaying a previously-computed
 * {@see \Milpa\Live\ValueObjects\InteractionResult}) or veto the action
 * outright. Readonly like every event VO in the family — the mutable
 * escape hatch lives entirely in the slot dispatched alongside this event,
 * never on the event itself. See
 * {@see \Milpa\Live\Events\LiveEventEmitter::withHandling()} for the exact
 * short-circuit/veto/passthrough contract.
 */
final readonly class ComponentHandlingEvent
{
    public function __construct(
        public InteractionRequest $request,
    ) {
    }
}
