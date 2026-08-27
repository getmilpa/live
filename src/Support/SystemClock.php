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

/** The wall clock: reads the real time. Non-deterministic by design — inject a {@see FixedClock} for replay. */
final class SystemClock implements Clock
{
    /** The real current instant, ISO-8601 — non-deterministic. */
    public function now(): string
    {
        return date('c');
    }
}
