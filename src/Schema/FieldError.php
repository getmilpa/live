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

namespace Milpa\Live\Schema;

/**
 * One validation failure on a field: a stable machine `code` (switch on this) plus a human `message`
 * (never parsed). Codes are a closed set: required, invalid_integer, invalid_number, invalid_boolean,
 * below_minimum, above_maximum, too_short, too_long, pattern_mismatch, not_in_enum.
 */
final readonly class FieldError
{
    public function __construct(public string $code, public string $message)
    {
    }
}
