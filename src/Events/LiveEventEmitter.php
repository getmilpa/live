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

namespace Milpa\Live\Events;

use Milpa\Events\InterceptionSlot;
use Milpa\Interfaces\Event\MilpaEventDispatcherInterface;
use Milpa\Live\Http\LiveHttpResponse;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\RenderRequest;
use Milpa\Live\ValueObjects\RenderResult;
use Milpa\Live\ValueObjects\SecurityPrincipal;
use Milpa\Live\ValueObjects\StateSnapshot;

/**
 * The single place every emit point in this package's component/render/
 * HTTP lifecycle goes through — one small set of static helpers instead of
 * duplicating the "construct a slot, dispatch, read it back" dance at each
 * of the call sites (`AutocompleteComponent`, `AbstractDashboardComponent`
 * + `DataTableComponent`, `AbstractFieldComponent`, the four HTML/TUI
 * renderers, `LiveEndpoint`).
 *
 * Every method here is nullable-safe: `$dispatcher === null` means every
 * `with*()` helper below runs its `$compute` closure directly and no event
 * is ever constructed or dispatched — byte-identical to this package's
 * pre-event behavior. This is the load-bearing property the whole retrofit
 * rests on (see
 * `docs/superpowers/specs/2026-07-08-event-driven-familia-design.md`
 * §milpa/live: "el dispatcher entra como dep opcional; sin él los
 * componentes corren igual").
 */
final class LiveEventEmitter
{
    private function __construct()
    {
        // Static-only utility — never instantiated.
    }

    /**
     * Wraps a `mount()` call with `component.mounting` (PRE, no slot) /
     * `component.mounted` (POST, no slot). Mount has no interception seam
     * in this catalog — see {@see ComponentMountingEvent}.
     *
     * @param array<string, mixed>      $props
     * @param \Closure(): StateSnapshot $compute
     */
    public static function withMounting(
        ?MilpaEventDispatcherInterface $dispatcher,
        string $componentName,
        array $props,
        ComponentContext $context,
        \Closure $compute,
    ): StateSnapshot {
        $dispatcher?->dispatch('component.mounting', [
            'event' => new ComponentMountingEvent($componentName, $props, $context),
        ]);

        $state = $compute();

        $dispatcher?->dispatch('component.mounted', [
            'event' => new ComponentMountedEvent($componentName, $props, $context, $state),
        ]);

        return $state;
    }

    /**
     * Wraps a `handle()` call with `component.handling` (PRE, slot) /
     * `component.handled` (POST, no slot). A `component.handling` listener
     * may:
     * - short-circuit (`InterceptionSlot::shortCircuit($interactionResult)`)
     *   to answer on the component's behalf — `$compute` never runs;
     * - veto (`InterceptionSlot::stop()`) — `$compute` never runs; the
     *   veto is reported back to the caller via `InteractionResult::$errors`
     *   rather than silently doing nothing, per
     *   {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface::handle()}'s
     *   own "SHOULD report via errors, not throw" contract;
     * - do neither — `$compute` runs exactly as it would with no dispatcher wired.
     *
     * @param \Closure(): InteractionResult $compute
     *
     * @throws \LogicException If a listener short-circuits with a value that is not an
     *                         {@see InteractionResult} — the documented contract for this slot.
     */
    public static function withHandling(
        ?MilpaEventDispatcherInterface $dispatcher,
        InteractionRequest $request,
        \Closure $compute,
    ): InteractionResult {
        $slot = new InterceptionSlot();
        $dispatcher?->dispatch('component.handling', [
            'event' => new ComponentHandlingEvent($request),
            'slot' => $slot,
        ]);

        $intercepted = $slot->hasResult() || $slot->isStopped();

        if ($slot->hasResult()) {
            $shortCircuited = $slot->getResult();
            if (!$shortCircuited instanceof InteractionResult) {
                throw new \LogicException(
                    'component.handling short-circuited with a non-InteractionResult value; '
                    . 'a listener MUST call shortCircuit() with an InteractionResult.'
                );
            }

            $result = $shortCircuited;
        } elseif ($slot->isStopped()) {
            $result = new InteractionResult(
                state: $request->state,
                errors: ['action' => "Action '{$request->action}' was vetoed by a component.handling listener."],
            );
        } else {
            $result = $compute();
        }

        $dispatcher?->dispatch('component.handled', [
            'event' => new ComponentHandledEvent($request, $result, $intercepted),
        ]);

        return $result;
    }

    /**
     * Wraps a `render()` call with `component.rendering` (PRE, slot) /
     * `component.rendered` (POST, no slot). See {@see withHandling()} for
     * the short-circuit/veto/passthrough shape — identical here, just for
     * {@see RenderResult} instead of {@see InteractionResult}. A pure veto
     * (`stop()` with no result) yields an empty {@see RenderResult} for
     * this target rather than running the renderer.
     *
     * @param \Closure(): RenderResult $compute
     *
     * @throws \LogicException If a listener short-circuits with a value that is not a
     *                         {@see RenderResult} — the documented contract for this slot.
     */
    public static function withRendering(
        ?MilpaEventDispatcherInterface $dispatcher,
        string $componentName,
        RenderRequest $request,
        \Closure $compute,
    ): RenderResult {
        $slot = new InterceptionSlot();
        $dispatcher?->dispatch('component.rendering', [
            'event' => new ComponentRenderingEvent($componentName, $request),
            'slot' => $slot,
        ]);

        $intercepted = $slot->hasResult() || $slot->isStopped();

        if ($slot->hasResult()) {
            $shortCircuited = $slot->getResult();
            if (!$shortCircuited instanceof RenderResult) {
                throw new \LogicException(
                    'component.rendering short-circuited with a non-RenderResult value; '
                    . 'a listener MUST call shortCircuit() with a RenderResult.'
                );
            }

            $result = $shortCircuited;
        } elseif ($slot->isStopped()) {
            $result = new RenderResult(output: '', state: $request->state, format: $request->target);
        } else {
            $result = $compute();
        }

        $dispatcher?->dispatch('component.rendered', [
            'event' => new ComponentRenderedEvent($componentName, $request, $result, $intercepted),
        ]);

        return $result;
    }

    /**
     * Dispatches `live.request` (PRE, slot) — see {@see LiveRequestEvent}
     * for the security-anchor placement contract. Returns the slot for
     * {@see \Milpa\Live\Http\LiveEndpoint::handle()} to read back; unlike
     * the other `with*()` helpers here, `LiveEndpoint` needs to branch on
     * the outcome itself (a short-circuit skips `handle()` but still
     * re-renders with the intercepted state; a veto skips both and returns
     * an error response directly), so this one hands back the raw slot
     * instead of taking a `$compute` closure.
     */
    public static function liveRequest(
        ?MilpaEventDispatcherInterface $dispatcher,
        InteractionRequest $interaction,
        ?SecurityPrincipal $principal,
    ): InterceptionSlot {
        $slot = new InterceptionSlot();
        $dispatcher?->dispatch('live.request', [
            'event' => new LiveRequestEvent($interaction, $principal),
            'slot' => $slot,
        ]);

        return $slot;
    }

    /**
     * Dispatches `live.responded` (POST, no slot) — see {@see LiveRespondedEvent}.
     */
    public static function liveResponded(
        ?MilpaEventDispatcherInterface $dispatcher,
        InteractionRequest $interaction,
        LiveHttpResponse $response,
        bool $intercepted,
    ): void {
        $dispatcher?->dispatch('live.responded', [
            'event' => new LiveRespondedEvent($interaction, $response, $intercepted),
        ]);
    }
}
