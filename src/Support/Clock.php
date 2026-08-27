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

namespace Milpa\Live\Support;

/**
 * Time as an INPUT, not a hidden call. A component that stamps time reads it from an injected {@see Clock}
 * rather than calling `date()`/`time()` inside its logic, so the same inputs and the same clock produce the
 * same output — the determinism a replay needs (greenhouse decisions/0102; the gap ResearchLabs surfaced when
 * its ActorRuntime used a wall clock). {@see SystemClock} is the wall; {@see FixedClock} is a reproducible one.
 */
interface Clock
{
    /** The current instant as an ISO-8601 string (the shape a document/state stamps). */
    public function now(): string;
}
