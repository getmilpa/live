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
 * A render- and transport-agnostic definition of a form: an id plus an ordered list of fields.
 * Pure data — parsing, coercion and validation live in SchemaFormParser / FormBinder, never here.
 */
final readonly class FormDefinition
{
    /** @param list<FormField> $fields */
    public function __construct(public string $id, public array $fields)
    {
    }

    /**
     * Serializes this definition to a transport-agnostic array (the inverse of fromArray).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            // array_values() defends the emitted payload's list<> invariant even if this instance
            // was constructed with a non-list $fields; the @param list<> is a promise, not a
            // runtime guarantee, so the redundancy hint below is expected and silenced narrowly.
            // @phpstan-ignore arrayValues.list
            'fields' => array_values(array_map(static fn (FormField $f): array => [
                'name' => $f->name,
                'type' => $f->type->value,
                'label' => $f->label,
                'required' => $f->required,
                'default' => $f->default,
                'constraints' => [
                    'min' => $f->constraints->min,
                    'max' => $f->constraints->max,
                    'minLength' => $f->constraints->minLength,
                    'maxLength' => $f->constraints->maxLength,
                    'pattern' => $f->constraints->pattern?->raw,
                    'enumOptions' => $f->constraints->enumOptions,
                ],
            ], $this->fields)),
        ];
    }

    /**
     * Reconstructs a FormDefinition from its array form (a round-trip channel for already-validated data).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // $data is untrusted: its `fields` may arrive with non-sequential keys (built via
        // array_filter, decoded from a JSON object, etc.). Type it as a plain array — NOT a
        // list — so array_values() below is a genuine reindex, not a phpstan-redundant no-op.
        // Without it FormDefinition::$fields could silently become a non-list and break the
        // structural `==` round-trip this class guarantees.
        // fromArray is a round-trip channel for ALREADY-VALIDATED data: only SchemaFormParser is the
        // untrusted schema seam. The pattern is re-validated cheaply (RegexPattern::fromRaw compiles it);
        // enumOptions are rehydrated as-is because FormBinder re-coerces them by base type at compare time.
        /** @var array<array<string, mixed>> $rawFields */
        $rawFields = $data['fields'];
        $fields = array_values(array_map(static function (array $f): FormField {
            /** @var array{min: int|float|null, max: int|float|null, minLength: int|null, maxLength: int|null, pattern: string|null, enumOptions: list<int|float|string>|null} $c */
            $c = $f['constraints'];

            return new FormField(
                name: (string) $f['name'],
                type: FieldType::from((string) $f['type']),
                label: (string) $f['label'],
                required: (bool) $f['required'],
                default: $f['default'],
                constraints: new FieldConstraints(
                    min: $c['min'],
                    max: $c['max'],
                    minLength: $c['minLength'],
                    maxLength: $c['maxLength'],
                    pattern: self::rehydratePattern($c['pattern'], (string) $f['name']),
                    enumOptions: $c['enumOptions'],
                ),
            );
        }, $rawFields));

        return new self((string) $data['id'], $fields);
    }

    /** Rehydrates a pattern from its raw string, naming the field if it is malformed. */
    private static function rehydratePattern(?string $raw, string $field): ?RegexPattern
    {
        if ($raw === null) {
            return null;
        }
        try {
            return RegexPattern::fromRaw($raw);
        } catch (InvalidSchemaException) {
            throw InvalidSchemaException::patternInvalid($field);
        }
    }
}
