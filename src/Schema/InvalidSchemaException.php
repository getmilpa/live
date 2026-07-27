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
 * A supported-but-malformed schema (a developer error), detected at parse time by SchemaFormParser.
 * Distinct from a FieldError (user input) and a RegexExecutionException (engine failure). Surfaces
 * switch on the stable `schemaCode`; the message is human text and is never parsed.
 */
final class InvalidSchemaException extends \RuntimeException
{
    private function __construct(public readonly string $schemaCode, string $message)
    {
        parent::__construct($message);
    }

    /** Builds the error for an enum member that cannot be coerced to the field's base type. */
    public static function enumMemberInvalid(string $field, mixed $member, FieldType $type): self
    {
        return new self(
            'MILPA_SCHEMA_ENUM_INVALID',
            "Field \"{$field}\" declares an enum member of type " . get_debug_type($member)
                . " that cannot be coerced to {$type->value}.",
        );
    }

    /** Builds the error for an `enum` key present but with no valid members. */
    public static function enumEmpty(string $field): self
    {
        return new self('MILPA_SCHEMA_ENUM_EMPTY', "Field \"{$field}\" declares enum but no valid members (must be a non-empty array).");
    }

    /** Builds the error for a `pattern` that is not a valid regular expression. */
    public static function patternInvalid(string $field): self
    {
        return new self('MILPA_SCHEMA_PATTERN_INVALID', "Field \"{$field}\" declares a pattern that is not a valid regular expression.");
    }

    /** Builds the error for a malformed numeric or length constraint. */
    public static function constraintInvalid(string $field, string $constraint): self
    {
        return new self('MILPA_SCHEMA_CONSTRAINT_INVALID', "Field \"{$field}\" declares a malformed \"{$constraint}\" constraint.");
    }

    /** Builds the error for a `default` not coercible to the type or outside the enum. */
    public static function defaultInvalid(string $field, string $reason): self
    {
        return new self('MILPA_SCHEMA_DEFAULT_INVALID', "Field \"{$field}\" declares an invalid default: {$reason}.");
    }
}
