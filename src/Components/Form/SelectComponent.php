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
 * Single-select form primitive with server-rendered options and
 * persistent local state — normalizes an `options` prop (array or
 * JSON-encoded string, list or map) into {@see meta()}.
 */
final class SelectComponent extends AbstractFieldComponent
{
    /** This field's runtime contract (`value` state, `options` prop, four field actions). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'select',
            contractVersion: '0.3.0-candidate',
            summary: 'Single select form primitive with server-rendered options and persistent local state.',
            designContract: '@milpa/design:primitives/milpa-select.contract.json',
            defaultTemplate: 'components/select.latte',
            propsSchema: [
                'name' => ['type' => 'string', 'required' => true],
                'label' => ['type' => 'string', 'required' => false],
                'value' => ['type' => 'string', 'default' => ''],
                'options' => ['type' => 'array', 'default' => []],
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
            'placeholder' => (string) ($props['placeholder'] ?? ''),
            'options' => $this->options($props['options'] ?? []),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string, disabled?: bool}>
     */
    private function options(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $options = [];
        foreach ($raw as $key => $option) {
            if (is_array($option)) {
                $options[] = [
                    'value' => (string) ($option['value'] ?? $key),
                    'label' => (string) ($option['label'] ?? $option['value'] ?? $key),
                    'disabled' => $this->boolProp($option['disabled'] ?? false),
                ];
                continue;
            }

            $options[] = [
                'value' => (string) $key,
                'label' => (string) $option,
                'disabled' => false,
            ];
        }

        return $options;
    }
}
