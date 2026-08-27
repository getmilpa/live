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

/** A reproducible clock: always returns the instant it was constructed with. The determinism a replay needs. */
final class FixedClock implements Clock
{
    public function __construct(private readonly string $instant)
    {
    }

    /** The fixed instant this clock was constructed with. */
    public function now(): string
    {
        return $this->instant;
    }
}
