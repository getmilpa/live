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
 * The single source of scalar coercion shared by SchemaFormParser (enum members, defaults) and
 * FormBinder (input). Centralizing it here removes divergence between the two seams. The rules are
 * frozen: integer requires /^-?\d+$/ then a verbatim (int) cast (overflow clamps silently); number
 * uses is_numeric then (float); the empty check is always strict `=== ''` so "0" is not empty.
 */
final class FieldCoercion
{
    /** A normalized (trimmed, non-empty) string to an int, or null if it is not an integer literal. */
    public static function toInt(string $str): ?int
    {
        return preg_match('/^-?\d+$/', $str) === 1 ? (int) $str : null;
    }

    /** A normalized (trimmed, non-empty) string to a float, or null if it is not numeric. */
    public static function toFloat(string $str): ?float
    {
        return is_numeric($str) ? (float) $str : null;
    }

    /** A normalized (lowercased, trimmed) string to a bool via the frozen token sets, or null. */
    public static function toBool(string $str): ?bool
    {
        if (in_array($str, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }
        if (in_array($str, ['0', 'false', 'off', 'no', ''], true)) {
            return false;
        }

        return null;
    }

    /**
     * Coerces one enum member (or any non-boolean schema value) to the field's base type. Non-scalar
     * or boolean members are rejected (null) BEFORE any string cast — no E_WARNING is ever emitted.
     */
    public static function coerceEnumMember(mixed $value, FieldType $type): int|float|string|null
    {
        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            return null;
        }

        return match ($type) {
            FieldType::Text => (string) $value,
            FieldType::Integer => self::toInt(trim((string) $value)),
            FieldType::Number => self::toFloat(trim((string) $value)),
            FieldType::Boolean => null, // boolean enums are dropped upstream; defensive
        };
    }

    /**
     * Coerces a field default to the base type. Boolean accepts a native bool or a token string;
     * the other types reuse coerceEnumMember. null means "not coercible to this type".
     */
    public static function coerceDefault(mixed $value, FieldType $type): int|float|string|bool|null
    {
        if ($type === FieldType::Boolean) {
            if (is_bool($value)) {
                return $value;
            }

            return is_int($value) || is_float($value) || is_string($value)
                ? self::toBool(strtolower(trim((string) $value)))
                : null;
        }

        return self::coerceEnumMember($value, $type);
    }
}
