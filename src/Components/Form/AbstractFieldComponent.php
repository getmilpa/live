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

namespace Milpa\Live\Components\Form;

use Milpa\Interfaces\Event\MilpaEventDispatcherInterface;
use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Events\LiveEventEmitter;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\ComponentContract;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;

/**
 * Shared mount/handle plumbing for the form field primitive family
 * (input, textarea, select, checkbox): common `dirty`/`touched`/`error`
 * state, and the four `change`/`blur`/`reset`/`set-error` actions every
 * field supports. Concrete subclasses implement {@see contract()} and, when
 * their value isn't a plain string (e.g. checkbox's boolean), override
 * {@see valueKey()} / {@see initialValue()}.
 */
abstract class AbstractFieldComponent implements ComponentDefinitionInterface
{
    public function __construct(
        protected readonly ?MilpaEventDispatcherInterface $dispatcher = null,
    ) {
    }

    /** The subclass's runtime contract (name, props/state schema, actions). */
    abstract public static function contract(): ComponentContract;

    /**
     * Builds the initial state from {@see initialValue()} plus common
     * `dirty`/`touched`/`error` fields, and captures `name`/`label`/
     * `required`/`disabled`/`hint`/`persistKey` props (merged with
     * {@see meta()}) as mount-time meta.
     *
     * @param array<string, mixed> $props
     */
    public function mount(array $props, ComponentContext $context): StateSnapshot
    {
        $contract = static::contract();
        $value = $this->initialValue($props);

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
                    $this->valueKey() => $value,
                    'dirty' => false,
                    'touched' => false,
                    'error' => $this->nullableString($props['error'] ?? null),
                ],
                meta: array_merge([
                    'name' => (string) ($props['name'] ?? $context->componentId),
                    'label' => (string) ($props['label'] ?? ''),
                    'required' => $this->boolProp($props['required'] ?? false),
                    'disabled' => $this->boolProp($props['disabled'] ?? false),
                    'hint' => (string) ($props['hint'] ?? ''),
                    'persistKey' => isset($props['persistKey']) ? (string) $props['persistKey'] : null,
                    'storage' => (string) ($props['storage'] ?? 'local'),
                    'defaultValue' => $value,
                    'route' => $context->route,
                    'principal' => $context->principal,
                ], $this->meta($props)),
            ),
        );
    }

    /**
     * Dispatches to `change`/`set` (aliases), `blur`, `reset`, and
     * `set-error`; an unrecognized action is reported via
     * {@see InteractionResult::$errors}.
     */
    public function handle(InteractionRequest $request): InteractionResult
    {
        return LiveEventEmitter::withHandling(
            $this->dispatcher,
            $request,
            fn (): InteractionResult => match ($request->action) {
                'change', 'set' => $this->change($request),
                'blur' => $this->blur($request),
                'reset' => $this->reset($request),
                'set-error' => $this->setError($request),
                default => new InteractionResult(
                    state: $request->state,
                    errors: ['action' => "Unknown form field action: {$request->action}"],
                ),
            },
        );
    }

    protected function valueKey(): string
    {
        return 'value';
    }

    /**
     * @param array<string, mixed> $props
     */
    protected function initialValue(array $props): mixed
    {
        return (string) ($props['value'] ?? '');
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
            return in_array(strtolower($value), ['', '1', 'true', 'yes', 'checked', 'required', 'disabled'], true);
        }

        return (bool) $value;
    }

    private function change(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;
        $key = $this->valueKey();
        $value = $key === 'checked'
            ? $this->boolProp($request->payload['checked'] ?? $request->payload['value'] ?? false)
            : (string) ($request->payload['value'] ?? '');

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, [
                    $key => $value,
                    'dirty' => true,
                    'error' => null,
                ]),
                meta: $state->meta,
            ),
            effects: [['type' => 'persist']],
        );
    }

    private function blur(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, ['touched' => true]),
                meta: $state->meta,
            ),
            effects: [['type' => 'persist']],
        );
    }

    private function reset(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;
        $key = $this->valueKey();

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, [
                    $key => $state->meta['defaultValue'] ?? ($key === 'checked' ? false : ''),
                    'dirty' => false,
                    'touched' => false,
                    'error' => null,
                ]),
                meta: $state->meta,
            ),
            effects: [['type' => 'forget']],
        );
    }

    private function setError(InteractionRequest $request): InteractionResult
    {
        $state = $request->state;

        return new InteractionResult(
            state: new StateSnapshot(
                componentId: $state->componentId,
                componentName: $state->componentName,
                version: $state->version,
                data: array_merge($state->data, [
                    'error' => $this->nullableString($request->payload['error'] ?? null),
                ]),
                meta: $state->meta,
            ),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
