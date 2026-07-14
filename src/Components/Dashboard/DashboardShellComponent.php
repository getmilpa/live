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

namespace Milpa\Live\Components\Dashboard;

use Milpa\Live\ValueObjects\ComponentContract;

/**
 * Root dashboard layout shell with slot-based composition (topbar,
 * sidebar, main) — a pure layout container, no actions.
 */
final class DashboardShellComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (`id`/`title`/`density` props). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'dashboard-shell',
            contractVersion: '0.6.0-candidate',
            summary: 'Root dashboard layout shell with slot-based composition.',
            designContract: '@milpa/design:components/milpa-shell.contract.json',
            defaultTemplate: 'components/dashboard-shell.latte',
            propsSchema: [
                'id' => ['type' => 'string', 'required' => false],
                'title' => ['type' => 'string', 'required' => false],
                'density' => ['type' => 'string', 'default' => 'comfortable'],
                'childrenHtml' => ['type' => 'string', 'required' => false],
            ],
            stateSchema: ['ready' => ['type' => 'boolean']],
        );
    }

    protected function meta(array $props): array
    {
        return [
            'density' => (string) ($props['density'] ?? 'comfortable'),
        ];
    }
}
