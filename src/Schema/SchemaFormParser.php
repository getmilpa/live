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
 * Parses a JSON-Schema-shaped input schema (`{type:object, properties:{...}, required:[...]}`) into a
 * FormDefinition. Supports the base types string/integer/number/boolean; enum is read as a constraint.
 * Properties whose type is outside that subset are dropped (a deferred type cannot be a field yet).
 *
 * Parser doctrine: SchemaForm never silently degrades a supported contract. An unsupported capability
 * may be omitted. A supported capability declared with an invalid shape is a producer error and fails
 * the parse.
 */
final class SchemaFormParser
{
    private const TYPE_MAP = [
        'string' => FieldType::Text,
        'integer' => FieldType::Integer,
        'number' => FieldType::Number,
        'boolean' => FieldType::Boolean,
    ];

    /**
     * Parses a JSON-Schema-shaped input schema into a FormDefinition, failing fast on a malformed supported contract.
     *
     * @param array<string, mixed> $inputSchema
     */
    public function fromSchema(string $id, array $inputSchema): FormDefinition
    {
        /** @var array<string, array<string, mixed>> $properties */
        $properties = is_array($inputSchema['properties'] ?? null) ? $inputSchema['properties'] : [];
        /** @var list<string> $required */
        $required = is_array($inputSchema['required'] ?? null) ? array_map('strval', $inputSchema['required']) : [];

        $fields = [];
        foreach ($properties as $name => $prop) {
            $jsonType = is_string($prop['type'] ?? null) ? $prop['type'] : null;
            $type = self::TYPE_MAP[$jsonType] ?? null;
            if ($type === null) {
                continue; // deferred type: drop BEFORE reading enum/pattern/constraints
            }

            $fieldName = (string) $name;
            $description = is_string($prop['description'] ?? null) ? $prop['description'] : null;
            $enumOptions = self::parseEnum($prop, $type, $fieldName);

            $fields[] = new FormField(
                name: $fieldName,
                type: $type,
                label: $description ?? self::humanize($fieldName),
                required: in_array($fieldName, $required, true),
                default: self::parseDefault($prop, $type, $enumOptions, $fieldName),
                constraints: new FieldConstraints(
                    min: self::numConstraint($prop, 'minimum', $fieldName),
                    max: self::numConstraint($prop, 'maximum', $fieldName),
                    minLength: self::intConstraint($prop, 'minLength', $fieldName),
                    maxLength: self::intConstraint($prop, 'maxLength', $fieldName),
                    pattern: self::parsePattern($prop, $fieldName),
                    enumOptions: $enumOptions,
                ),
            );
        }

        // array_values() defends the list<> invariant FormDefinition promises even if this
        // loop's construction changes later; phpstan can already prove $fields is a list here,
        // so the redundancy hint below is expected and silenced narrowly (mirrors
        // FormDefinition::toArray()'s identical defensive array_values()).
        // @phpstan-ignore arrayValues.list
        return new FormDefinition($id, array_values($fields));
    }

    /**
     * @param array<string, mixed> $prop
     *
     * @return list<int|float|string>|null
     */
    private static function parseEnum(array $prop, FieldType $type, string $field): ?array
    {
        if (! array_key_exists('enum', $prop)) {
            return null;
        }
        if ($type === FieldType::Boolean) {
            return null; // fail-soft: boolean enum is an unimplemented capability, dropped like a deferred type
        }
        if (! is_array($prop['enum']) || $prop['enum'] === []) {
            throw InvalidSchemaException::enumEmpty($field);
        }

        $coerced = [];
        foreach (array_values($prop['enum']) as $member) {
            $c = FieldCoercion::coerceEnumMember($member, $type);
            if ($c === null) {
                throw InvalidSchemaException::enumMemberInvalid($field, $member, $type);
            }
            $coerced[] = $c;
        }

        return $coerced;
    }

    /**
     * @param array<string, mixed>        $prop
     * @param list<int|float|string>|null $enumOptions
     */
    private static function parseDefault(array $prop, FieldType $type, ?array $enumOptions, string $field): mixed
    {
        $default = $prop['default'] ?? null;
        if ($default === null) {
            return null;
        }

        $coerced = FieldCoercion::coerceDefault($default, $type);
        if ($coerced === null) {
            throw InvalidSchemaException::defaultInvalid($field, "not coercible to {$type->value}");
        }
        if ($enumOptions !== null && ! in_array($coerced, $enumOptions, true)) {
            throw InvalidSchemaException::defaultInvalid($field, 'not in enum');
        }

        return $coerced;
    }

    /** @param array<string, mixed> $prop */
    private static function numConstraint(array $prop, string $key, string $field): int|float|null
    {
        if (! array_key_exists($key, $prop)) {
            return null;
        }
        if (is_int($prop[$key]) || is_float($prop[$key])) {
            return $prop[$key];
        }

        throw InvalidSchemaException::constraintInvalid($field, $key);
    }

    /** @param array<string, mixed> $prop */
    private static function intConstraint(array $prop, string $key, string $field): ?int
    {
        if (! array_key_exists($key, $prop)) {
            return null;
        }
        if (is_int($prop[$key])) {
            return $prop[$key];
        }

        throw InvalidSchemaException::constraintInvalid($field, $key);
    }

    /** @param array<string, mixed> $prop */
    private static function parsePattern(array $prop, string $field): ?RegexPattern
    {
        if (! array_key_exists('pattern', $prop) || $prop['pattern'] === null) {
            return null; // pattern not declared — a supported capability simply omitted (fail-soft)
        }
        if (! is_string($prop['pattern'])) {
            throw InvalidSchemaException::patternInvalid($field); // declared with an invalid shape — producer error (fail-fast)
        }

        try {
            return RegexPattern::fromRaw($prop['pattern']);
        } catch (InvalidSchemaException) {
            throw InvalidSchemaException::patternInvalid($field);
        }
    }

    private static function humanize(string $name): string
    {
        $spaced = preg_replace('/(?<!^)[A-Z]/', ' $0', str_replace('_', ' ', $name)) ?? $name;

        return ucfirst(strtolower($spaced));
    }
}
