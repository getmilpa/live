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
 * Dashboard panel primitive for grouped operational content — title/
 * description/span/tone props flow into {@see meta()}; no actions.
 */
final class DashboardPanelComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (`title`/`description`/`span`/`tone` props). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'dashboard-panel',
            contractVersion: '0.6.0-candidate',
            summary: 'Dashboard panel primitive for grouped operational content.',
            designContract: '@milpa/design:components/milpa-card.contract.json',
            defaultTemplate: 'components/dashboard-panel.latte',
            propsSchema: [
                'title' => ['type' => 'string', 'required' => false],
                'description' => ['type' => 'string', 'required' => false],
                'span' => ['type' => 'integer', 'default' => 1],
                'tone' => ['type' => 'string', 'default' => 'default'],
                'childrenHtml' => ['type' => 'string', 'required' => false],
            ],
            stateSchema: ['ready' => ['type' => 'boolean']],
        );
    }

    protected function meta(array $props): array
    {
        return [
            'description' => (string) ($props['description'] ?? ''),
            'span' => max(1, min(6, (int) ($props['span'] ?? 1))),
            'tone' => (string) ($props['tone'] ?? 'default'),
        ];
    }
}
