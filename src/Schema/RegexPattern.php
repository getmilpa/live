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
 * A validated regular expression: it cannot exist unless it compiles. `fromRaw` is the only way to
 * build one, so an uncompilable pattern is rejected at construction (a schema error), never at match
 * time. `matches` distinguishes a genuine non-match from an engine failure (backtrack/recursion limit)
 * — the latter is a RegexExecutionException, never a user-facing pattern_mismatch.
 */
final readonly class RegexPattern
{
    private function __construct(public string $raw)
    {
    }

    /** Builds a RegexPattern from a raw string, throwing InvalidSchemaException if it does not compile. */
    public static function fromRaw(string $pattern): self
    {
        $delimited = self::delimit($pattern);

        $failed = false;
        set_error_handler(static function () use (&$failed): bool {
            $failed = true;

            return true; // swallow the compile warning without @ or global state
        });

        try {
            $result = preg_match($delimited, '');
        } finally {
            restore_error_handler();
        }

        if ($failed || $result === false) {
            throw InvalidSchemaException::patternInvalid('');
        }

        return new self($pattern);
    }

    /** Tests whether the value matches, throwing RegexExecutionException on an engine failure. */
    public function matches(string $value): bool
    {
        $result = preg_match(self::delimit($this->raw), $value);
        if ($result === false) {
            throw new RegexExecutionException(
                'Regex engine failed to evaluate a compiled pattern (error code ' . preg_last_error() . ').',
            );
        }

        return $result === 1;
    }

    private static function delimit(string $raw): string
    {
        return '/' . str_replace('/', '\\/', $raw) . '/';
    }
}
