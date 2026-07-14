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

namespace Milpa\Live\Components\Autocomplete;

use Milpa\Interfaces\Event\MilpaEventDispatcherInterface;
use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Contracts\Data\DataSourceRegistryInterface;
use Milpa\Live\Events\LiveEventEmitter;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\ComponentContract;
use Milpa\Live\ValueObjects\DataSourceRequest;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;

/**
 * Live autocomplete primitive: a search box backed by an injectable
 * {@see DataSourceRegistryInterface}, with `search`/`select`/`remove`/`clear`
 * actions and optional single- or multi-select state. Render-target-agnostic
 * like every component here — this class only owns {@see contract()},
 * {@see mount()}, and {@see handle()}; turning its state into markup/TUI is a
 * {@see \Milpa\Live\Contracts\Rendering\ComponentRendererInterface}'s job.
 */
final readonly class AutocompleteComponent implements ComponentDefinitionInterface
{
    public function __construct(
        private DataSourceRegistryInterface $dataSources,
        private ?MilpaEventDispatcherInterface $dispatcher = null,
    ) {
    }

    /**
     * The autocomplete's runtime contract: props/state schema plus its four
     * declared actions (`search`, `select`, `remove`, `clear`).
     */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: 'autocomplete',
            contractVersion: '0.1.0',
            summary: 'Live autocomplete primitive with injectable data source and optional persisted state.',
            designContract: '@milpa/design:primitives/milpa-input.contract.json',
            defaultTemplate: 'components/autocomplete.latte',
            propsSchema: [
                'name' => ['type' => 'string', 'required' => true],
                'label' => ['type' => 'string', 'required' => false],
                'source' => ['type' => 'string', 'required' => true],
                'limit' => ['type' => 'integer', 'default' => 20],
                'multiple' => ['type' => 'boolean', 'default' => false],
                'persistKey' => ['type' => 'string', 'required' => false],
            ],
            stateSchema: [
                'query' => ['type' => 'string'],
                'selected' => ['type' => 'array'],
                'items' => ['type' => 'array'],
                'open' => ['type' => 'boolean'],
                'loading' => ['type' => 'boolean'],
                'error' => ['type' => 'string|null'],
            ],
            actions: [
                'search' => ['payload' => ['query' => 'string']],
                'select' => ['payload' => ['item' => 'object']],
                'remove' => ['payload' => ['item' => 'object']],
                'clear' => ['payload' => []],
            ],
            dataSources: [
                'source' => ['kind' => 'list', 'shape' => ['value', 'label']],
            ],
        );
    }

    /**
     * Builds the initial state from `source`/`query`/`selected`/`limit`/
     * `multiple` props; requires a non-empty `source` prop.
     *
     * @throws \InvalidArgumentException If `source` is missing or empty.
     */
    public function mount(array $props, ComponentContext $context): StateSnapshot
    {
        $contract = self::contract();

        return LiveEventEmitter::withMounting(
            $this->dispatcher,
            $contract->name,
            $props,
            $context,
            function () use ($props, $context, $contract): StateSnapshot {
                $source = trim((string) ($props['source'] ?? ''));

                if ($source === '') {
                    throw new \InvalidArgumentException('Autocomplete requires a non-empty source prop.');
                }

                return new StateSnapshot(
                    componentId: $context->componentId,
                    componentName: $contract->name,
                    version: $contract->contractVersion,
                    data: [
                        'query' => (string) ($props['query'] ?? ''),
                        'selected' => $this->selectedList($props['selected'] ?? []),
                        'items' => [],
                        'open' => false,
                        'loading' => false,
                        'error' => null,
                    ],
                    meta: [
                        'source' => $source,
                        'limit' => (int) ($props['limit'] ?? 20),
                        'multiple' => $this->boolProp($props['multiple'] ?? false),
                        'name' => (string) ($props['name'] ?? $context->componentId),
                        'label' => (string) ($props['label'] ?? ''),
                        'persistKey' => isset($props['persistKey']) ? (string) $props['persistKey'] : null,
                        'route' => $context->route,
                        'principal' => $context->principal,
                    ],
                );
            },
        );
    }

    /**
     * Dispatches to `search`/`select`/`remove`/`clear`; an unrecognized
     * action is reported via {@see InteractionResult::$errors} rather than
     * thrown, per {@see ComponentDefinitionInterface::handle()}'s contract.
     */
    public function handle(InteractionRequest $request): InteractionResult
    {
        return LiveEventEmitter::withHandling(
            $this->dispatcher,
            $request,
            fn (): InteractionResult => match ($request->action) {
                'search' => $this->search($request),
                'select' => $this->select($request),
                'remove' => $this->remove($request),
                'clear' => $this->clear($request),
                default => new InteractionResult(
                    state: $request->state,
                    errors: ['action' => "Unknown autocomplete action: {$request->action}"],
                ),
            },
        );
    }

    private function search(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;
        $source = (string) ($state->meta['source'] ?? '');
        $query = (string) ($request->payload['query'] ?? '');
        $limit = max(1, (int) ($state->meta['limit'] ?? 20));

        try {
            $dataSource = $this->dataSources->resolve($source);
            $result = $dataSource->resolve(new DataSourceRequest(
                source: $source,
                componentId: $request->componentId,
                query: $query,
                limit: $limit,
                context: [
                    'componentName' => $request->componentName,
                    'principal' => $state->meta['principal'] ?? null,
                    'route' => $state->meta['route'] ?? null,
                ],
            ));
            $selected = $this->selectedList($state->data['selected'] ?? []);
            $items = array_values(array_filter(
                $result->items,
                fn (array $item): bool => !$this->containsItem($selected, $item),
            ));

            return new InteractionResult(
                state: new StateSnapshot(
                    componentId: $state->componentId,
                    componentName: $state->componentName,
                    version: $state->version,
                    data: array_merge($state->data, [
                        'query' => $query,
                        'items' => $items,
                        'open' => true,
                        'loading' => false,
                        'error' => null,
                    ]),
                    meta: array_merge($state->meta, ['dataSource' => $result->meta]),
                ),
            );
        } catch (\Throwable $e) {
            return new InteractionResult(
                state: new StateSnapshot(
                    componentId: $state->componentId,
                    componentName: $state->componentName,
                    version: $state->version,
                    data: array_merge($state->data, [
                        'query' => $query,
                        'items' => [],
                        'open' => false,
                        'loading' => false,
                        'error' => $e->getMessage(),
                    ]),
                    meta: $state->meta,
                ),
                errors: ['source' => $e->getMessage()],
            );
        }
    }

    private function select(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;
        $item = $request->payload['item'] ?? null;

        if (!is_array($item)) {
            return new InteractionResult(
                state: $state,
                errors: ['item' => 'Autocomplete select requires an item payload.'],
            );
        }

        $selected = $this->selectedList($state->data['selected'] ?? []);
        $multiple = (bool) ($state->meta['multiple'] ?? false);

        if (!$this->containsItem($selected, $item)) {
            $selected = $multiple ? [...$selected, $item] : [$item];
        }

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, [
                    'query' => '',
                    'selected' => $selected,
                    'open' => false,
                    'items' => [],
                    'error' => null,
                ]),
                meta: $state->meta,
            ),
            effects: [['type' => 'persist']],
        );
    }

    private function remove(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;
        $item = $request->payload['item'] ?? null;

        if (!is_array($item)) {
            return new InteractionResult(
                state: $state,
                errors: ['item' => 'Autocomplete remove requires an item payload.'],
            );
        }

        $selected = array_values(array_filter(
            $this->selectedList($state->data['selected'] ?? []),
            fn (array $candidate): bool => !$this->sameItem($candidate, $item),
        ));

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, [
                    'selected' => $selected,
                    'error' => null,
                ]),
                meta: $state->meta,
            ),
            effects: [['type' => count($selected) > 0 ? 'persist' : 'forget']],
        );
    }

    private function clear(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, [
                    'query' => '',
                    'selected' => [],
                    'items' => [],
                    'open' => false,
                    'error' => null,
                ]),
                meta: $state->meta,
            ),
            effects: [['type' => 'forget']],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function selectedList(mixed $selected): array
    {
        if (!is_array($selected)) {
            return [];
        }

        if ($selected === []) {
            return [];
        }

        if (array_is_list($selected)) {
            return array_values(array_filter($selected, 'is_array'));
        }

        return [$selected];
    }

    /**
     * @param array<int, array<string, mixed>> $selected
     * @param array<string, mixed>             $item
     */
    private function containsItem(array $selected, array $item): bool
    {
        foreach ($selected as $candidate) {
            if ($this->sameItem($candidate, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private function sameItem(array $a, array $b): bool
    {
        return (string) ($a['value'] ?? $a['label'] ?? '') === (string) ($b['value'] ?? $b['label'] ?? '');
    }

    private function boolProp(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['', '1', 'true', 'yes', 'multiple'], true);
        }

        return (bool) $value;
    }
}
