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
 * Dashboard page heading primitive — eyebrow/title/description props,
 * no state beyond the {@see AbstractDashboardComponent} default, no actions.
 */
final class DashboardPageHeaderComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (`eyebrow`/`title`/`description` props). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'dashboard-page-header',
            contractVersion: '0.10.0-candidate',
            summary: 'Dashboard page heading primitive.',
            designContract: '@milpa/design:components/milpa-page-header.contract.json',
            defaultTemplate: 'components/dashboard-page-header.latte',
            propsSchema: [
                'eyebrow' => ['type' => 'string', 'required' => false],
                'title' => ['type' => 'string', 'required' => true],
                'description' => ['type' => 'string', 'required' => false],
            ],
            stateSchema: ['ready' => ['type' => 'boolean']],
        );
    }

    protected function meta(array $props): array
    {
        return [
            'eyebrow' => (string) ($props['eyebrow'] ?? ''),
            'description' => (string) ($props['description'] ?? ''),
        ];
    }
}
