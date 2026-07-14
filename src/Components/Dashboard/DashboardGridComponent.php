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
 * Responsive dashboard grid primitive — clamps `columns` to 1-6 and passes
 * `gap` through to {@see meta()}; a pure layout container, no actions.
 */
final class DashboardGridComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (`columns`/`gap` layout props). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'dashboard-grid',
            contractVersion: '0.6.0-candidate',
            summary: 'Responsive dashboard grid primitive.',
            designContract: '@milpa/components-lab:adapter/mcl-dashboard-grid',
            defaultTemplate: 'components/dashboard-grid.latte',
            propsSchema: [
                'columns' => ['type' => 'integer', 'default' => 3],
                'gap' => ['type' => 'string', 'default' => 'md'],
                'childrenHtml' => ['type' => 'string', 'required' => false],
            ],
            stateSchema: ['ready' => ['type' => 'boolean']],
        );
    }

    protected function meta(array $props): array
    {
        return [
            'columns' => max(1, min(6, (int) ($props['columns'] ?? 3))),
            'gap' => (string) ($props['gap'] ?? 'md'),
        ];
    }
}
