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
 * Boolean form primitive with persistent local state — the one form
 * primitive whose {@see AbstractFieldComponent::valueKey()} is `checked`
 * (a bool) rather than `value` (a string).
 */
final class CheckboxComponent extends AbstractFieldComponent
{
    /** This field's runtime contract (`checked` state, four field actions). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'checkbox',
            contractVersion: '0.3.0-candidate',
            summary: 'Boolean form primitive with persistent local state.',
            designContract: '@milpa/design:primitives/milpa-checkbox.contract.json',
            defaultTemplate: 'components/checkbox.latte',
            propsSchema: [
                'name' => ['type' => 'string', 'required' => true],
                'label' => ['type' => 'string', 'required' => false],
                'checked' => ['type' => 'boolean', 'default' => false],
                'value' => ['type' => 'string', 'default' => '1'],
                'required' => ['type' => 'boolean', 'default' => false],
                'disabled' => ['type' => 'boolean', 'default' => false],
                'hint' => ['type' => 'string', 'required' => false],
                'error' => ['type' => 'string|null', 'required' => false],
                'persistKey' => ['type' => 'string', 'required' => false],
                'storage' => ['type' => 'string', 'default' => 'local'],
            ],
            stateSchema: [
                'checked' => ['type' => 'boolean'],
                'dirty' => ['type' => 'boolean'],
                'touched' => ['type' => 'boolean'],
                'error' => ['type' => 'string|null'],
            ],
            actions: [
                'change' => ['payload' => ['checked' => 'boolean']],
                'blur' => ['payload' => []],
                'reset' => ['payload' => []],
                'set-error' => ['payload' => ['error' => 'string|null']],
            ],
        );
    }

    protected function valueKey(): string
    {
        return 'checked';
    }

    protected function initialValue(array $props): mixed
    {
        return $this->boolProp($props['checked'] ?? false);
    }

    protected function meta(array $props): array
    {
        return [
            'value' => (string) ($props['value'] ?? '1'),
        ];
    }
}
