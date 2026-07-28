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
use Milpa\Live\Schema\FormField;
use PHPUnit\Framework\TestCase;

final class FormFieldTest extends TestCase
{
    public function test_field_type_cases(): void
    {
        self::assertSame('text', FieldType::Text->value);
        self::assertSame('integer', FieldType::Integer->value);
        self::assertSame('number', FieldType::Number->value);
        self::assertSame('boolean', FieldType::Boolean->value);
        self::assertFalse(defined(FieldType::class . '::Enum'));
        self::assertFalse(defined(FieldType::class . '::Textarea'));
    }

    public function test_field_holds_its_shape(): void
    {
        $field = new FormField(
            name: 'retries',
            type: FieldType::Integer,
            label: 'Retries',
            required: false,
            default: 3,
            constraints: new FieldConstraints(min: 0, max: 5),
        );

        self::assertSame('retries', $field->name);
        self::assertSame(FieldType::Integer, $field->type);
        self::assertSame(3, $field->default);
        self::assertSame(0, $field->constraints->min);
        self::assertSame(5, $field->constraints->max);
        self::assertNull($field->constraints->enumOptions);
    }

    public function test_constraints_accept_float_bounds_and_enum_options(): void
    {
        $c = new FieldConstraints(min: 0.5, max: 9.5, enumOptions: ['light', 'dark']);
        self::assertSame(0.5, $c->min);
        self::assertSame(['light', 'dark'], $c->enumOptions);
    }
}
