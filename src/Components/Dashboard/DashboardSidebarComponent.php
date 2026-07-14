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
 * Dashboard navigation sidebar primitive — normalizes an `items` prop
 * (accepting either an array or a JSON-encoded string) into
 * {@see meta()}; no actions.
 */
final class DashboardSidebarComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract (`brand`/`active`/`items` props). */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'dashboard-sidebar',
            contractVersion: '0.6.0-candidate',
            summary: 'Dashboard navigation sidebar primitive.',
            designContract: '@milpa/design:components/milpa-sidebar.contract.json',
            defaultTemplate: 'components/dashboard-sidebar.latte',
            propsSchema: [
                'brand' => ['type' => 'string', 'required' => false],
                'active' => ['type' => 'string', 'required' => false],
                'items' => ['type' => 'array', 'default' => []],
                'childrenHtml' => ['type' => 'string', 'required' => false],
            ],
            stateSchema: ['ready' => ['type' => 'boolean']],
        );
    }

    protected function meta(array $props): array
    {
        return [
            'brand' => (string) ($props['brand'] ?? 'Milpa'),
            'active' => (string) ($props['active'] ?? ''),
            'items' => $this->items($props['items'] ?? []),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, href: string}>
     */
    private function items(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $key => $item) {
            if (is_array($item)) {
                $items[] = [
                    'key' => (string) ($item['key'] ?? $key),
                    'label' => (string) ($item['label'] ?? $item['key'] ?? $key),
                    'href' => (string) ($item['href'] ?? '#'),
                ];
            }
        }

        return $items;
    }
}
