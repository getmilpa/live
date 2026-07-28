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

use Milpa\Live\Schema\FieldType;
use Milpa\Live\Schema\InvalidSchemaException;
use PHPUnit\Framework\TestCase;

final class InvalidSchemaExceptionTest extends TestCase
{
    public function test_factories_carry_stable_codes_and_name_the_field(): void
    {
        $enum = InvalidSchemaException::enumMemberInvalid('retries', ['x'], FieldType::Integer);
        self::assertSame('MILPA_SCHEMA_ENUM_INVALID', $enum->schemaCode);
        self::assertStringContainsString('retries', $enum->getMessage());
        self::assertStringContainsString('array', $enum->getMessage()); // non-scalar rendered safely, no warning

        self::assertSame('MILPA_SCHEMA_ENUM_EMPTY', InvalidSchemaException::enumEmpty('theme')->schemaCode);
        self::assertStringContainsString('theme', InvalidSchemaException::enumEmpty('theme')->getMessage());

        self::assertSame('MILPA_SCHEMA_PATTERN_INVALID', InvalidSchemaException::patternInvalid('slug')->schemaCode);
        self::assertStringContainsString('slug', InvalidSchemaException::patternInvalid('slug')->getMessage());

        self::assertSame('MILPA_SCHEMA_CONSTRAINT_INVALID', InvalidSchemaException::constraintInvalid('n', 'minimum')->schemaCode);
        self::assertStringContainsString('n', InvalidSchemaException::constraintInvalid('n', 'minimum')->getMessage());

        self::assertSame('MILPA_SCHEMA_DEFAULT_INVALID', InvalidSchemaException::defaultInvalid('theme', 'not in enum')->schemaCode);
        self::assertStringContainsString('theme', InvalidSchemaException::defaultInvalid('theme', 'not in enum')->getMessage());
    }

    public function test_native_code_slot_is_untouched(): void
    {
        // the stable code lives in $schemaCode, NOT in \Exception::$code (int)
        self::assertSame(0, InvalidSchemaException::enumEmpty('x')->getCode());
    }
}
