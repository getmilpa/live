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
 * Binds raw (typically all-string) input against a FormDefinition: coerces each value to the field's
 * base type per the frozen contract, validates against the field's constraints, and returns a
 * FormSubmission with normalized values for EVERY field (typed where valid, raw-or-null otherwise) and
 * a per-field ValidationResult. It never dispatches.
 */
final class FormBinder
{
    private const ABSENT = "\0__absent__\0";

    /**
     * Binds raw input against a FormDefinition and returns a typed, validated FormSubmission.
     *
     * @param array<string, mixed> $rawValues
     */
    public function bind(FormDefinition $definition, array $rawValues): FormSubmission
    {
        $values = [];
        $errors = [];

        foreach ($definition->fields as $field) {
            $raw = array_key_exists($field->name, $rawValues) ? $rawValues[$field->name] : self::ABSENT;
            [$value, $fieldErrors] = $this->bindField($field, $raw);
            $values[$field->name] = $value;
            if ($fieldErrors !== []) {
                $errors[$field->name] = $fieldErrors;
            }
        }

        return new FormSubmission($values, new ValidationResult($errors === [], $errors));
    }

    /** @return array{0: mixed, 1: list<FieldError>} */
    private function bindField(FormField $field, mixed $raw): array
    {
        return match ($field->type) {
            FieldType::Boolean => $this->bindBoolean($field, $raw),
            FieldType::Integer => $this->bindNumber($field, $raw, true),
            FieldType::Number => $this->bindNumber($field, $raw, false),
            FieldType::Text => $this->bindText($field, $raw),
        };
    }

    /** @return array{0: mixed, 1: list<FieldError>} */
    private function bindText(FormField $field, mixed $raw): array
    {
        if ($raw === self::ABSENT) {
            return [$field->default, $field->required ? [new FieldError('required', "{$field->label} is required.")] : []];
        }
        // A non-scalar raw (e.g. `field[]=x` parsed into an array, or an object) has no usable
        // text value: casting it would emit a warning ("Array to string conversion") or fatal.
        // Treat it like an absent value — fall back to the default, and error `required` if required.
        // (An explicit null is intentionally left on the empty-string path below — existing behavior.)
        if (is_array($raw) || is_object($raw)) {
            return [$field->default, $field->required ? [new FieldError('required', "{$field->label} is required.")] : []];
        }
        $value = is_string($raw) ? $raw : (string) $raw;
        if ($value === '') {
            return ['', $field->required ? [new FieldError('required', "{$field->label} is required.")] : []];
        }

        $errors = [];
        $c = $field->constraints;
        if ($c->minLength !== null && mb_strlen($value) < $c->minLength) {
            $errors[] = new FieldError('too_short', "{$field->label} is too short.");
        }
        if ($c->maxLength !== null && mb_strlen($value) > $c->maxLength) {
            $errors[] = new FieldError('too_long', "{$field->label} is too long.");
        }
        if ($c->pattern !== null && ! $c->pattern->matches($value)) {
            $errors[] = new FieldError('pattern_mismatch', "{$field->label} has an invalid format.");
        }
        if ($c->enumOptions !== null && ! self::inEnum($value, $c->enumOptions, $field->type)) {
            $errors[] = new FieldError('not_in_enum', "{$field->label} is not an allowed option.");
        }

        return [$value, $errors];
    }

    /** @return array{0: mixed, 1: list<FieldError>} */
    private function bindNumber(FormField $field, mixed $raw, bool $integer): array
    {
        $code = $integer ? 'invalid_integer' : 'invalid_number';
        if ($raw === self::ABSENT) {
            return [$field->default, $field->required ? [new FieldError('required', "{$field->label} is required.")] : []];
        }
        $str = is_string($raw) ? trim($raw) : (is_int($raw) || is_float($raw) ? (string) $raw : self::ABSENT);
        if ($str === '') {
            return [null, $field->required ? [new FieldError('required', "{$field->label} is required.")] : []];
        }
        if ($str === self::ABSENT) {
            return [$raw, [new FieldError($code, "{$field->label} must be a valid " . ($integer ? 'integer' : 'number') . '.')]];
        }
        $coerced = $integer ? FieldCoercion::toInt($str) : FieldCoercion::toFloat($str);
        if ($coerced === null) {
            return [$raw, [new FieldError($code, "{$field->label} must be a valid " . ($integer ? 'integer' : 'number') . '.')]];
        }
        $value = $coerced;
        $errors = [];
        $c = $field->constraints;
        if ($c->min !== null && $value < $c->min) {
            $errors[] = new FieldError('below_minimum', "{$field->label} is below the minimum.");
        }
        if ($c->max !== null && $value > $c->max) {
            $errors[] = new FieldError('above_maximum', "{$field->label} is above the maximum.");
        }
        if ($c->enumOptions !== null && ! self::inEnum($value, $c->enumOptions, $field->type)) {
            $errors[] = new FieldError('not_in_enum', "{$field->label} is not an allowed option.");
        }

        return [$value, $errors];
    }

    /** @return array{0: mixed, 1: list<FieldError>}  required NEVER applies to boolean */
    private function bindBoolean(FormField $field, mixed $raw): array
    {
        if ($raw === self::ABSENT) {
            return [is_bool($field->default) ? $field->default : false, []];
        }
        if ($raw === true) {
            return [true, []];
        }
        if ($raw === false) {
            return [false, []];
        }
        if (is_string($raw)) {
            $bool = FieldCoercion::toBool(strtolower(trim($raw)));
            if ($bool !== null) {
                return [$bool, []];
            }
        }

        return [$raw, [new FieldError('invalid_boolean', "{$field->label} must be a boolean.")]];
    }

    /**
     * Re-coerces each declared option to the field's base type before the strict compare, so the match
     * survives a JSON round-trip (which recasts 2.0 -> 2) and a hand-built FieldConstraints.
     *
     * @param list<int|float|string> $options
     */
    private static function inEnum(int|float|string $value, array $options, FieldType $type): bool
    {
        foreach ($options as $option) {
            if (FieldCoercion::coerceEnumMember($option, $type) === $value) {
                return true;
            }
        }

        return false;
    }
}
