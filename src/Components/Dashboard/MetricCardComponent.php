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
 * Compact KPI/metric primitive for dashboard summaries — mutable
 * `value`/`delta`/`trend` state via a single `set-value` action, distinct
 * from the mostly-static dashboard primitives around it.
 */
final class MetricCardComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (`title`/`value`/`delta`/`trend` props, `set-value` action). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'metric-card',
            contractVersion: '0.6.0-candidate',
            summary: 'Compact KPI/metric primitive for dashboard summaries.',
            designContract: '@milpa/design:components/milpa-stat.contract.json',
            defaultTemplate: 'components/metric-card.latte',
            propsSchema: [
                'title' => ['type' => 'string', 'required' => true],
                'value' => ['type' => 'string', 'required' => true],
                'delta' => ['type' => 'string', 'required' => false],
                'trend' => ['type' => 'string', 'default' => 'neutral'],
                'caption' => ['type' => 'string', 'required' => false],
            ],
            stateSchema: [
                'value' => ['type' => 'string'],
                'delta' => ['type' => 'string'],
                'trend' => ['type' => 'string'],
            ],
            actions: [
                'set-value' => ['payload' => ['value' => 'string', 'delta' => 'string', 'trend' => 'string']],
            ],
        );
    }

    protected function initialData(array $props): array
    {
        return [
            'value' => (string) ($props['value'] ?? ''),
            'delta' => (string) ($props['delta'] ?? ''),
            'trend' => (string) ($props['trend'] ?? 'neutral'),
        ];
    }

    protected function meta(array $props): array
    {
        return [
            'caption' => (string) ($props['caption'] ?? ''),
        ];
    }
}
