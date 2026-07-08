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

use Milpa\Live\Events\LiveEventEmitter;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\ComponentContract;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;

/**
 * Selectable dashboard data table primitive — row selection, sorting, and
 * pagination state, driven entirely by server-rendered `columns`/`rows`
 * props (no client-side data fetching). The one dashboard primitive besides
 * {@see MetricCardComponent} that overrides {@see mount()} and
 * {@see handle()} instead of just {@see AbstractDashboardComponent::meta()}.
 */
final class DataTableComponent extends AbstractDashboardComponent
{
    /** This primitive's runtime contract: selection/sort/page state, six actions. */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'data-table',
            contractVersion: '0.6.0-candidate',
            summary: 'Selectable dashboard data table primitive.',
            designContract: '@milpa/design:components/milpa-table.contract.json',
            defaultTemplate: 'components/data-table.latte',
            propsSchema: [
                'name' => ['type' => 'string', 'required' => true],
                'caption' => ['type' => 'string', 'required' => false],
                'columns' => ['type' => 'array', 'default' => []],
                'rows' => ['type' => 'array', 'default' => []],
                'selectable' => ['type' => 'boolean', 'default' => false],
                'selectedRows' => ['type' => 'array', 'default' => []],
                'persistKey' => ['type' => 'string', 'required' => false],
                'storage' => ['type' => 'string', 'default' => 'local'],
            ],
            stateSchema: [
                'selectedRows' => ['type' => 'array'],
                'sortBy' => ['type' => 'string'],
                'sortDirection' => ['type' => 'string'],
                'page' => ['type' => 'integer'],
                'error' => ['type' => 'string|null'],
            ],
            actions: [
                'toggle-row' => ['payload' => ['rowId' => 'string']],
                'select-row' => ['payload' => ['rowId' => 'string']],
                'unselect-row' => ['payload' => ['rowId' => 'string']],
                'clear-selection' => ['payload' => []],
                'sort' => ['payload' => ['key' => 'string']],
                'page' => ['payload' => ['page' => 'integer']],
            ],
        );
    }

    /**
     * Builds the initial selection/sort/page state from props, and captures
     * the (normalized) `columns`/`rows`/`selectable`/`persistKey` props as
     * mount-time meta the table's actions do not rewrite.
     */
    public function mount(array $props, ComponentContext $context): StateSnapshot
    {
        $contract = self::contract();

        return LiveEventEmitter::withMounting(
            $this->dispatcher,
            $contract->name,
            $props,
            $context,
            fn (): StateSnapshot => new StateSnapshot(
                componentId: $context->componentId,
                componentName: $contract->name,
                version: $contract->contractVersion,
                data: [
                    'selectedRows' => $this->stringList($props['selectedRows'] ?? []),
                    'sortBy' => (string) ($props['sortBy'] ?? ''),
                    'sortDirection' => (string) ($props['sortDirection'] ?? 'asc'),
                    'page' => max(1, (int) ($props['page'] ?? 1)),
                    'error' => null,
                ],
                meta: [
                    'name' => (string) ($props['name'] ?? $context->componentId),
                    'caption' => (string) ($props['caption'] ?? ''),
                    'columns' => $this->columns($props['columns'] ?? []),
                    'rows' => $this->rows($props['rows'] ?? []),
                    'selectable' => $this->boolProp($props['selectable'] ?? false),
                    'persistKey' => isset($props['persistKey']) ? (string) $props['persistKey'] : null,
                    'storage' => (string) ($props['storage'] ?? 'local'),
                    'route' => $context->route,
                    'principal' => $context->principal,
                ],
            ),
        );
    }

    /**
     * Dispatches to the six declared row-selection/sort/page actions; an
     * unrecognized action is reported via {@see InteractionResult::$errors}.
     */
    public function handle(InteractionRequest $request): InteractionResult
    {
        return LiveEventEmitter::withHandling(
            $this->dispatcher,
            $request,
            fn (): InteractionResult => match ($request->action) {
                'toggle-row' => $this->toggleRow($request),
                'select-row' => $this->selectRow($request),
                'unselect-row' => $this->unselectRow($request),
                'clear-selection' => $this->replaceSelection($request, []),
                'sort' => $this->sort($request),
                'page' => $this->page($request),
                default => new InteractionResult(
                    state: $request->state,
                    errors: ['action' => "Unknown data table action: {$request->action}"],
                ),
            },
        );
    }

    private function toggleRow(InteractionRequest $request): InteractionResult
    {
        $rowId = (string) ($request->payload['rowId'] ?? '');
        if ($rowId === '') {
            return new InteractionResult($request->state, errors: ['rowId' => 'Data table row id is required.']);
        }

        $selected = $this->stringList($request->state->data['selectedRows'] ?? []);
        if (in_array($rowId, $selected, true)) {
            $selected = array_values(array_filter($selected, static fn (string $id): bool => $id !== $rowId));
        } else {
            $selected[] = $rowId;
        }

        return $this->replaceSelection($request, $selected);
    }

    private function selectRow(InteractionRequest $request): InteractionResult
    {
        $rowId = (string) ($request->payload['rowId'] ?? '');
        if ($rowId === '') {
            return new InteractionResult($request->state, errors: ['rowId' => 'Data table row id is required.']);
        }

        $selected = $this->stringList($request->state->data['selectedRows'] ?? []);
        if (!in_array($rowId, $selected, true)) {
            $selected[] = $rowId;
        }

        return $this->replaceSelection($request, $selected);
    }

    private function unselectRow(InteractionRequest $request): InteractionResult
    {
        $rowId = (string) ($request->payload['rowId'] ?? '');
        $selected = array_values(array_filter(
            $this->stringList($request->state->data['selectedRows'] ?? []),
            static fn (string $id): bool => $id !== $rowId,
        ));

        return $this->replaceSelection($request, $selected);
    }

    /**
     * @param array<int, string> $selected
     */
    private function replaceSelection(InteractionRequest $request, array $selected): InteractionResult
    {
        $state = $request->state;

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, [
                    'selectedRows' => array_values(array_unique($selected)),
                    'error' => null,
                ]),
                meta: $state->meta,
            ),
            effects: [['type' => count($selected) > 0 ? 'persist' : 'forget']],
        );
    }

    private function sort(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;
        $key = (string) ($request->payload['key'] ?? '');
        $currentKey = (string) ($state->data['sortBy'] ?? '');
        $currentDirection = (string) ($state->data['sortDirection'] ?? 'asc');
        $direction = $key === $currentKey && $currentDirection === 'asc' ? 'desc' : 'asc';

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, [
                    'sortBy' => $key,
                    'sortDirection' => $direction,
                    'error' => null,
                ]),
                meta: $state->meta,
            ),
            effects: [['type' => 'persist']],
        );
    }

    private function page(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, [
                    'page' => max(1, (int) ($request->payload['page'] ?? 1)),
                    'error' => null,
                ]),
                meta: $state->meta,
            ),
            effects: [['type' => 'persist']],
        );
    }

    /**
     * @return array<int, array{key: string, label: string, align: string}>
     */
    private function columns(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $columns = [];
        foreach ($raw as $key => $column) {
            if (is_array($column)) {
                $columns[] = [
                    'key' => (string) ($column['key'] ?? $key),
                    'label' => (string) ($column['label'] ?? $column['key'] ?? $key),
                    'align' => (string) ($column['align'] ?? 'left'),
                ];
            }
        }

        return $columns;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_filter($raw, 'is_array'));
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_map('strval', array_filter($raw, static fn (mixed $value): bool => $value !== null && $value !== '')));
    }
}
