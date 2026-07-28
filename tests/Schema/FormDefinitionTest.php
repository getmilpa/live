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

use Milpa\Live\Schema\FieldConstraints;
use Milpa\Live\Schema\FieldType;
use Milpa\Live\Schema\FormDefinition;
use Milpa\Live\Schema\FormField;
use PHPUnit\Framework\TestCase;

final class FormDefinitionTest extends TestCase
{
    public function test_round_trip_is_structurally_equal(): void
    {
        $def = new FormDefinition('settings:update-demo', [
            new FormField('siteName', FieldType::Text, 'Site name', true, null, new FieldConstraints(maxLength: 120)),
            new FormField('retries', FieldType::Integer, 'Retries', false, 3, new FieldConstraints(min: 0, max: 5)),
            new FormField('theme', FieldType::Text, 'Theme', false, 'light', new FieldConstraints(enumOptions: ['light', 'dark'])),
        ]);

        self::assertEquals($def, FormDefinition::fromArray($def->toArray()));
    }

    public function test_to_array_exposes_field_shape(): void
    {
        $def = new FormDefinition('f', [
            new FormField('n', FieldType::Boolean, 'N', false, false, new FieldConstraints()),
        ]);
        $arr = $def->toArray();
        self::assertSame('f', $arr['id']);
        self::assertSame('boolean', $arr['fields'][0]['type']);
        self::assertFalse($arr['fields'][0]['default']);
    }

    public function test_from_array_normalizes_non_sequential_fields_to_a_list(): void
    {
        // Untrusted upstream data may arrive with non-sequential keys — e.g. built via
        // array_filter, or decoded from a JSON object like {"0": ..., "2": ...}. fromArray
        // must reindex so FormDefinition::$fields is a genuine list<FormField>; otherwise the
        // structural `==` round-trip this class exists to guarantee silently breaks
        // (PHP `==` requires matching key sets: [0,1] != [0,2] even with equal values).
        $field = [
            'name' => 'a',
            'type' => 'text',
            'label' => 'A',
            'required' => false,
            'default' => null,
            'constraints' => [
                'min' => null,
                'max' => null,
                'minLength' => null,
                'maxLength' => null,
                'pattern' => null,
                'enumOptions' => null,
            ],
        ];
        $sparse = [0 => $field, 2 => $field];

        $def = FormDefinition::fromArray(['id' => 'x', 'fields' => $sparse]);

        // $def->fields is statically typed list<FormField> — a phpdoc promise. We runtime-verify
        // the reindex actually happened; phpstan proving it "always true" is exactly that promise,
        // and this test exists to guard the runtime reality behind it.
        // @phpstan-ignore function.alreadyNarrowedType, staticMethod.alreadyNarrowedType
        self::assertTrue(array_is_list($def->fields));
        self::assertTrue(array_is_list($def->toArray()['fields']));
    }
}
