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

use Milpa\Interfaces\Event\MilpaEventDispatcherInterface;
use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Events\LiveEventEmitter;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\ComponentContract;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;

/**
 * Shared mount/handle plumbing for the dashboard primitive family
 * (shell, topbar, sidebar, panel, grid, metric card, data table, …):
 * concrete subclasses only implement {@see contract()} and, optionally,
 * {@see initialData()} / {@see meta()} to describe their own props — this
 * base class owns wiring {@see \Milpa\Live\Events\LiveEventEmitter}'s
 * mounting/handling emit points and the default "no actions" `handle()`.
 */
abstract class AbstractDashboardComponent implements ComponentDefinitionInterface
{
    public function __construct(
        protected readonly ?MilpaEventDispatcherInterface $dispatcher = null,
    ) {
    }

    /** The subclass's runtime contract (name, props/state schema, actions). */
    abstract public static function contract(): ComponentContract;

    /**
     * Builds the initial state from {@see initialData()} (the `data` half)
     * and {@see meta()} merged with common `id`/`title`/`route`/`principal`
     * meta (the `meta` half).
     *
     * @param array<string, mixed> $props
     */
    public function mount(array $props, ComponentContext $context): StateSnapshot
    {
        $contract = static::contract();

        return LiveEventEmitter::withMounting(
            $this->dispatcher,
            $contract->name,
            $props,
            $context,
            fn (): StateSnapshot => new StateSnapshot(
                componentId: $context->componentId,
                componentName: $contract->name,
                version: $contract->contractVersion,
                data: $this->initialData($props),
                meta: array_merge([
                    'id' => (string) ($props['id'] ?? $context->componentId),
                    'title' => (string) ($props['title'] ?? ''),
                    'route' => $context->route,
                    'principal' => $context->principal,
                ], $this->meta($props)),
            ),
        );
    }

    /**
     * The default dashboard primitive `handle()`: none of these primitives
     * declare actions, so every call reports an unrecognized-action error via
     * {@see InteractionResult::$errors}. A subclass that does need actions
     * (e.g. {@see DataTableComponent}) overrides this method entirely.
     */
    public function handle(InteractionRequest $request): InteractionResult
    {
        return LiveEventEmitter::withHandling(
            $this->dispatcher,
            $request,
            fn (): InteractionResult => new InteractionResult(
                state: $request->state,
                errors: ['action' => "Dashboard primitive does not handle action: {$request->action}"],
            ),
        );
    }

    /**
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    protected function initialData(array $props): array
    {
        return ['ready' => true];
    }

    /**
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    protected function meta(array $props): array
    {
        return [];
    }

    protected function boolProp(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['', '1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }
}
