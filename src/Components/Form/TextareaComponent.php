<?php

/**
 * This file is part of Milpa Live — the render-target-agnostic live component core of the Milpa PHP framework.
 *
 * (c) TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\Components\Form;

use Milpa\Live\ValueObjects\ComponentContract;

/**
 * Multiline text form primitive with persistent local state — a plain
 * string `value` like {@see InputComponent}, plus a `rows` display prop.
 */
final class TextareaComponent extends AbstractFieldComponent
{
    /** This field's runtime contract (`value` state, `rows` prop, four field actions). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'textarea',
            contractVersion: '0.3.0-candidate',
            summary: 'Multiline text form primitive with persistent local state.',
            designContract: '@milpa/design:primitives/milpa-textarea.contract.json',
            defaultTemplate: 'components/textarea.latte',
            propsSchema: [
                'name' => ['type' => 'string', 'required' => true],
                'label' => ['type' => 'string', 'required' => false],
                'value' => ['type' => 'string', 'default' => ''],
                'placeholder' => ['type' => 'string', 'required' => false],
                'rows' => ['type' => 'integer', 'default' => 4],
                'required' => ['type' => 'boolean', 'default' => false],
                'disabled' => ['type' => 'boolean', 'default' => false],
                'hint' => ['type' => 'string', 'required' => false],
                'error' => ['type' => 'string|null', 'required' => false],
                'persistKey' => ['type' => 'string', 'required' => false],
                'storage' => ['type' => 'string', 'default' => 'local'],
            ],
            stateSchema: [
                'value' => ['type' => 'string'],
                'dirty' => ['type' => 'boolean'],
                'touched' => ['type' => 'boolean'],
                'error' => ['type' => 'string|null'],
            ],
            actions: [
                'change' => ['payload' => ['value' => 'string']],
                'blur' => ['payload' => []],
                'reset' => ['payload' => []],
                'set-error' => ['payload' => ['error' => 'string|null']],
            ],
        );
    }

    protected function meta(array $props): array
    {
        return [
            'placeholder' => (string) ($props['placeholder'] ?? ''),
            'rows' => max(1, (int) ($props['rows'] ?? 4)),
        ];
    }
}
