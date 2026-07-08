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

namespace Milpa\Live\Components\Dashboard;

use Milpa\Live\ValueObjects\ComponentContract;

/**
 * Dashboard topbar action button primitive — no state beyond
 * {@see AbstractDashboardComponent}'s `ready` default; its label/variant/
 * size/type props flow straight into {@see meta()}.
 */
final class DashboardActionButtonComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (label/variant/size/type props). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'dashboard-action-button',
            contractVersion: '0.10.0-candidate',
            summary: 'Dashboard topbar action button primitive.',
            designContract: '@milpa/design:primitives/milpa-button.contract.json',
            defaultTemplate: 'components/dashboard-action-button.latte',
            propsSchema: [
                'label' => ['type' => 'string', 'required' => true],
                'variant' => ['type' => 'string', 'default' => 'ghost'],
                'size' => ['type' => 'string', 'default' => 'sm'],
                'type' => ['type' => 'string', 'default' => 'button'],
            ],
            stateSchema: ['ready' => ['type' => 'boolean']],
        );
    }

    protected function meta(array $props): array
    {
        return [
            'label' => (string) ($props['label'] ?? ''),
            'variant' => (string) ($props['variant'] ?? 'ghost'),
            'size' => (string) ($props['size'] ?? 'sm'),
            'type' => (string) ($props['type'] ?? 'button'),
        ];
    }
}
