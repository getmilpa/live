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
 * Main dashboard workspace primitive — the content region a
 * {@see DashboardShellComponent} wraps; no state beyond the
 * {@see AbstractDashboardComponent} default, no actions.
 */
final class DashboardMainComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (an optional `id` prop). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'dashboard-main',
            contractVersion: '0.6.0-candidate',
            summary: 'Main dashboard workspace primitive.',
            designContract: '@milpa/design:components/milpa-shell.contract.json',
            defaultTemplate: 'components/dashboard-main.latte',
            propsSchema: [
                'id' => ['type' => 'string', 'required' => false],
                'childrenHtml' => ['type' => 'string', 'required' => false],
            ],
            stateSchema: ['ready' => ['type' => 'boolean']],
        );
    }
}
