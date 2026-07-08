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
 * Single-value form input primitive with persistent local state — a plain
 * string `value`, using {@see AbstractFieldComponent}'s defaults as-is.
 */
final class InputComponent extends AbstractFieldComponent
{
    /** This field's runtime contract (`value` state, four field actions). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'input',
            contractVersion: '0.3.0-candidate',
            summary: 'Single value form input primitive with persistent local state.',
            designContract: '@milpa/design:primitives/milpa-input.contract.json',
            defaultTemplate: 'components/input.latte',
            propsSchema: [
                'name' => ['type' => 'string', 'required' => true],
                'label' => ['type' => 'string', 'required' => false],
                'type' => ['type' => 'string', 'default' => 'text'],
                'value' => ['type' => 'string', 'default' => ''],
                'placeholder' => ['type' => 'string', 'required' => false],
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
            'type' => (string) ($props['type'] ?? 'text'),
            'placeholder' => (string) ($props['placeholder'] ?? ''),
        ];
    }
}
