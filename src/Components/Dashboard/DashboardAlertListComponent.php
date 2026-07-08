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
 * Dashboard attention/alert list primitive — normalizes an `items` prop
 * (count + text pairs) into {@see meta()}; no interactive actions.
 */
final class DashboardAlertListComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (an `items` array prop). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'dashboard-alert-list',
            contractVersion: '0.10.0-candidate',
            summary: 'Dashboard attention list primitive.',
            designContract: '@milpa/design:components/milpa-alert.contract.json',
            defaultTemplate: 'components/dashboard-alert-list.latte',
            propsSchema: [
                'items' => ['type' => 'array', 'default' => []],
            ],
            stateSchema: ['ready' => ['type' => 'boolean']],
        );
    }

    protected function meta(array $props): array
    {
        return [
            'items' => $this->items($props['items'] ?? []),
        ];
    }

    /**
     * @return array<int, array{count: string, text: string}>
     */
    private function items(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized[] = [
                'count' => (string) ($item['count'] ?? ''),
                'text' => (string) ($item['text'] ?? ''),
            ];
        }

        return $normalized;
    }
}
