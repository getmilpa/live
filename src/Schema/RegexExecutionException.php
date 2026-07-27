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
 * The regex ENGINE failed to run a pattern that DID compile (e.g. a backtrack/recursion limit hit at
 * match time). This is neither a schema error nor invalid user input — it is a runtime engine failure,
 * so it is never reported to the user as a pattern_mismatch FieldError.
 */
final class RegexExecutionException extends \RuntimeException
{
}
