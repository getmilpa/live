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
use Milpa\Live\ValueObjects\SecurityPrincipal;

/**
 * PRE event: an HTTP live interaction has passed every security gate
 * (method, CSRF, state-envelope signature/nonce verification,
 * contract-based authorization) and is about to be dispatched to the
 * component's own `handle()`.
 *
 * Dispatched by {@see \Milpa\Live\Http\LiveEndpoint::handle()} as
 * `live.request`, ALWAYS alongside a {@see \Milpa\Events\InterceptionSlot}.
 *
 * **Security anchor (non-negotiable).** This event is dispatched strictly
 * AFTER CSRF verification, state-envelope signature/nonce verification,
 * and {@see \Milpa\Live\Contracts\Security\InteractionAuthorizerInterface::authorize()}
 * have ALL already run and ALL already passed — never before. A listener
 * subscribed to `live.request` only ever gets a turn once every gate has
 * said yes; a short-circuit here can never be mistaken for an
 * authorization bypass. See {@see \Milpa\Live\Http\LiveEndpoint::handle()}
 * for the exact placement.
 */
final readonly class LiveRequestEvent
{
    public function __construct(
        public InteractionRequest $interaction,
        public ?SecurityPrincipal $principal = null,
    ) {
    }
}
