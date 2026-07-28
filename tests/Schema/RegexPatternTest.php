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

namespace Milpa\Live\Tests\Schema;

use Milpa\Live\Schema\InvalidSchemaException;
use Milpa\Live\Schema\RegexExecutionException;
use Milpa\Live\Schema\RegexPattern;
use PHPUnit\Framework\TestCase;

final class RegexPatternTest extends TestCase
{
    public function test_from_raw_stores_verbatim_and_matches(): void
    {
        $p = RegexPattern::fromRaw('^[a-z]+$');
        self::assertSame('^[a-z]+$', $p->raw);
        self::assertTrue($p->matches('abc'));
        self::assertFalse($p->matches('AB3'));
    }

    public function test_from_raw_escapes_the_delimiter_like_the_binder(): void
    {
        // a pattern containing the '/' delimiter must still compile and match (escape parity)
        $p = RegexPattern::fromRaw('^\d{2}/\d{2}$');
        self::assertTrue($p->matches('01/02'));
    }

    public function test_from_raw_throws_on_uncompilable_pattern_without_warning(): void
    {
        // PHPUnit fails the test if an E_WARNING leaks — this proves the compile-check is warning-free
        $this->expectException(InvalidSchemaException::class);
        RegexPattern::fromRaw('[unterminated');
    }

    public function test_matches_throws_on_engine_failure_not_a_false_negative(): void
    {
        // catastrophic backtracking: the pattern compiles, but the engine gives up at match time
        $p = RegexPattern::fromRaw('^(a+)+$');
        $this->expectException(RegexExecutionException::class);
        $p->matches(str_repeat('a', 100000) . '!');
    }

    public function test_value_equality_holds_for_the_round_trip(): void
    {
        self::assertEquals(RegexPattern::fromRaw('^x$'), RegexPattern::fromRaw('^x$'));
    }
}
