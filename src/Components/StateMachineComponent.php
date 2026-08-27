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

namespace Milpa\Live\Components;

use Milpa\Live\Components\Dashboard\AbstractDashboardComponent;
use Milpa\Live\Events\LiveEventEmitter;
use Milpa\Live\ValueObjects\ComponentContract;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;

/**
 * A CLOSED, declarative state machine over the live wire — the portable-interaction/v1 machine
 * (greenhouse decisions/0087) made executable in the runtime: the states, the initial state, the transition
 * table AND each transition's EFFECTS are DATA, and {@see handle()} is a pure application of that data — the
 * reducer IS the table, with no per-transition code. An event no transition declares does not advance the
 * state (a closed union, not an open handler); an action the contract does not declare never reaches here
 * (the endpoint's authorizer refuses it first); and an EFFECT of a type outside the allow-list is refused,
 * never applied. The owner rides every transition: `meta.principal` is preserved across each advance, so a
 * machine advances only for the actor it was mounted for (greenhouse decisions/0093, effects: decisions/0094).
 */
final class StateMachineComponent extends AbstractDashboardComponent
{
    private const NAME = 'state-machine';

    private const VERSION = '0.4.0';

    private const INITIAL = 'queued';

    /**
     * The whole behaviour, as data: from-state => (event => {to, effects}). Each `effects` entry is an
     * allow-listed declaration (see {@see allowedEffectTypes()}); a reviewer reads every reachable state, every
     * legal advance, and every side effect. Extending it is a version bump, never code.
     *
     * @var array<string, array<string, array{to: string, effects?: list<array<string, mixed>>}>>
     */
    private const TRANSITIONS = [
        'queued' => ['start' => ['to' => 'running', 'effects' => [['type' => 'emit', 'event' => 'sm.started']]]],
        'running' => [
            'finish' => ['to' => 'done', 'effects' => [['type' => 'set-variable', 'key' => 'result', 'value' => 'ok'], ['type' => 'emit', 'event' => 'sm.finished']]],
            'fail' => ['to' => 'failed', 'effects' => [['type' => 'set-variable', 'key' => 'result', 'value' => 'error'], ['type' => 'emit', 'event' => 'sm.failed']]],
        ],
        'done' => [],
        'failed' => [],
    ];

    /** The closed allow-list of effect types a transition may declare — extending it is a version bump, never code. */
    private const EFFECT_TYPES = ['emit', 'set-variable'];

    /** The machine's contract: a single `state` field and the declared events; the authorizer allow-lists these. */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: self::NAME,
            contractVersion: self::VERSION,
            summary: 'A closed declarative state machine: states, transitions and effects are data; the reducer is the table.',
            stateSchema: ['state' => ['type' => 'string']],
            actions: ['start' => [], 'finish' => [], 'fail' => [], 'fire' => ['payload' => ['event' => 'string'], 'scopeBy' => 'event']],
        );
    }

    /**
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    protected function initialData(array $props): array
    {
        $machine = $this->machineFrom($props);

        return ['state' => $machine['initial']];
    }

    /**
     * The machine as meta so it rides the SIGNED state (tamper-proof) and {@see handle()} reads it back — this
     * is what lets an app DECLARE its own machine via props without any code (greenhouse decisions/0095).
     *
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    protected function meta(array $props): array
    {
        return ['machine' => $this->machineFrom($props)];
    }

    /**
     * Resolve the machine from `props['machine']` when it is a well-formed spec, else the baked default. The
     * transition table's shape is validated here; the closed effect-type allow-list is enforced at transition
     * time in {@see handle()}, so an app-declared effect of a type outside the allow-list is still refused.
     *
     * @param array<string, mixed> $props
     *
     * @return array{initial: string, transitions: array<string, array<string, array{to: string, effects?: list<array<string, mixed>>}>>}
     */
    private function machineFrom(array $props): array
    {
        $spec = $props['machine'] ?? null;
        $transitions = \is_array($spec['transitions'] ?? null) ? $spec['transitions'] : null;
        $initial = \is_string($spec['initial'] ?? null) ? $spec['initial'] : null;

        if ($transitions === null || $initial === null || ! $this->wellFormed($transitions, $initial)) {
            return ['initial' => self::INITIAL, 'transitions' => self::TRANSITIONS];
        }

        return ['initial' => $initial, 'transitions' => $transitions];
    }

    /**
     * A machine spec is well-formed when the initial state exists (as a from-state or the target of some
     * transition) and every transition names a string target. It says nothing about effect TYPES — those are
     * the component's closed allow-list, checked when a transition fires.
     *
     * @param array<string, mixed> $transitions
     */
    private function wellFormed(array $transitions, string $initial): bool
    {
        $states = array_keys($transitions);
        foreach ($transitions as $events) {
            if (! \is_array($events)) {
                return false;
            }
            foreach ($events as $t) {
                $to = \is_array($t) ? ($t['to'] ?? null) : null;
                if (! \is_string($to) || $to === '') {
                    return false;
                }
                $states[] = $to;
            }
        }

        return \in_array($initial, $states, true);
    }

    /**
     * One event: advance along the declared transition and fire its allow-listed effects, or refuse — without
     * advancing — when no transition is declared or an effect is outside the allow-list.
     */
    public function handle(InteractionRequest $request): InteractionResult
    {
        return LiveEventEmitter::withHandling(
            $this->dispatcher,
            $request,
            function () use ($request): InteractionResult {
                $machine = \is_array($request->state->meta['machine'] ?? null) ? $request->state->meta['machine'] : ['transitions' => self::TRANSITIONS];
                $transitions = \is_array($machine['transitions'] ?? null) ? $machine['transitions'] : self::TRANSITIONS;
                $event = $request->action === 'fire'
                    ? (\is_string($request->payload['event'] ?? null) ? $request->payload['event'] : '')
                    : $request->action;
                $current = \is_string($request->state->data['state'] ?? null) ? $request->state->data['state'] : self::INITIAL;
                $transition = $transitions[$current][$event] ?? null;

                if ($transition === null) {
                    // Closed machine: an event no transition declares does not move the state.
                    return new InteractionResult(
                        state: $request->state,
                        errors: ['transition' => "No '{$event}' transition from '{$current}'."],
                    );
                }

                $data = $request->state->data;
                $data['state'] = $transition['to'];
                $emitted = [];

                foreach ($transition['effects'] ?? [] as $effect) {
                    $type = \is_string($effect['type'] ?? null) ? $effect['type'] : '';
                    if (! \in_array($type, $this->allowedEffectTypes(), true)) {
                        // Closed allow-list: an effect no allow-list declares is refused, and the state does NOT move.
                        return new InteractionResult(
                            state: $request->state,
                            errors: ['effect' => "Effect type '{$type}' is not allow-listed."],
                        );
                    }
                    if ($type === 'set-variable') {
                        $data[(string) ($effect['key'] ?? '')] = $effect['value'] ?? null;
                    } else { // 'emit' — a named event the host may observe, echoed to the client, never code
                        $emitted[] = ['type' => 'emit', 'event' => (string) ($effect['event'] ?? '')];
                    }
                }

                return new InteractionResult(
                    state: new StateSnapshot(
                        componentId: $request->state->componentId,
                        componentName: $request->state->componentName,
                        version: $request->state->version,
                        data: $data,
                        meta: $request->state->meta, // the owner rides the transition
                    ),
                    effects: $emitted,
                );
            },
        );
    }

    /**
     * The closed allow-list of effect types.
     *
     * @return list<string>
     */
    protected function allowedEffectTypes(): array
    {
        return self::EFFECT_TYPES;
    }
}
