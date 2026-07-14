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
 * Dashboard header/topbar primitive — title/subtitle/eyebrow/controls/
 * search-placeholder props flow into {@see meta()}; no actions.
 */
final class DashboardTopbarComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (title/subtitle/eyebrow/controls props). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'dashboard-topbar',
            contractVersion: '0.6.0-candidate',
            summary: 'Dashboard header/topbar primitive.',
            designContract: '@milpa/design:components/milpa-topbar.contract.json',
            defaultTemplate: 'components/dashboard-topbar.latte',
            propsSchema: [
                'title' => ['type' => 'string', 'required' => false],
                'subtitle' => ['type' => 'string', 'required' => false],
                'eyebrow' => ['type' => 'string', 'required' => false],
                'controls' => ['type' => 'string', 'required' => false],
                'searchPlaceholder' => ['type' => 'string', 'required' => false],
                'childrenHtml' => ['type' => 'string', 'required' => false],
            ],
            stateSchema: ['ready' => ['type' => 'boolean']],
        );
    }

    protected function meta(array $props): array
    {
        return [
            'subtitle' => (string) ($props['subtitle'] ?? ''),
            'eyebrow' => (string) ($props['eyebrow'] ?? ''),
            'controls' => (string) ($props['controls'] ?? ''),
            'searchPlaceholder' => (string) ($props['searchPlaceholder'] ?? ''),
        ];
    }
}
