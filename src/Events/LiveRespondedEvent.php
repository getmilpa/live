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

use Milpa\Live\Http\LiveHttpResponse;
use Milpa\Live\ValueObjects\InteractionRequest;

/**
 * POST event: {@see \Milpa\Live\Http\LiveEndpoint::handle()} is about to
 * return a response for a request that passed security and reached
 * `live.request`. A request rejected earlier (bad method, missing fields,
 * CSRF, invalid signature/replay, unknown component, unauthorized action)
 * never reaches `live.request` and therefore never reaches this event
 * either — those failures are visible to callers via the returned
 * {@see LiveHttpResponse} itself, not via an event.
 *
 * Dispatched as `live.responded` — pure notification, readonly, no slot.
 *
 * **Short-circuit visibility invariant.** Mirrors
 * {@see ComponentHandledEvent}: whether `live.request` was intercepted
 * (short-circuited or vetoed) or the component's own `handle()` ran
 * normally, this event MUST still fire, with {@see $intercepted} set
 * accordingly.
 */
final readonly class LiveRespondedEvent
{
    public function __construct(
        public InteractionRequest $interaction,
        public LiveHttpResponse $response,
        public bool $intercepted = false,
    ) {
    }
}
