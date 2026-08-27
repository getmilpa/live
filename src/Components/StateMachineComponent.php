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
use Milpa\Interfaces\Event\MilpaEventDispatcherInterface;
use Milpa\Live\Events\LiveEventEmitter;
use Milpa\Interfaces\Clock;
use Milpa\Support\SystemClock;
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

    private const VERSION = '0.12.0';

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
    private const EFFECT_TYPES = ['emit', 'set-variable', 'stamp'];

    /**
     * @param Clock $clock time as an INPUT: a `stamp` effect reads it from here, never from `date()`, so the
     *                     same inputs and the same clock replay to the same state (greenhouse decisions/0102).
     */
    public function __construct(?MilpaEventDispatcherInterface $dispatcher = null, private readonly Clock $clock = new SystemClock())
    {
        parent::__construct($dispatcher);
    }

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

        $machine = ['initial' => $initial, 'transitions' => $transitions];
        if (\is_array($spec['states'] ?? null)) {
            $machine['states'] = $spec['states']; // per-state onEnter/onExit, rides the signed state
        }

        return $machine;
    }

    /**
     * A machine spec is well-formed when the initial state exists (as a from-state or the target of some
     * transition) and every transition names a string target. It says nothing about effect TYPES — those are
     * the component's closed allow-list, checked when a transition fires.
     *
     * @param array<array-key, mixed> $transitions the array (list) form or the nested-map form
     */
    private function wellFormed(array $transitions, string $initial): bool
    {
        $states = [];
        if (array_is_list($transitions)) {
            // canonical ARRAY form [{from, on, to, when?, effects?}]
            foreach ($transitions as $t) {
                $from = \is_array($t) ? ($t['from'] ?? null) : null;
                $on = \is_array($t) ? ($t['on'] ?? null) : null;
                $to = \is_array($t) ? ($t['to'] ?? null) : null;
                if (! \is_string($from) || ! \is_string($on) || ! \is_string($to) || $to === '') {
                    return false;
                }
                $states[] = $from;
                $states[] = $to;
            }

            return \in_array($initial, $states, true);
        }

        // nested-MAP form {from: {event: {to, ...}}}
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
                [$transition, $error] = $this->selectTransition($transitions, $current, $event, $request->state->data);
                if ($transition === null) {
                    return new InteractionResult(state: $request->state, errors: $error);
                }

                $data = $request->state->data;
                $data['state'] = $transition['to'];
                $emitted = [];

                // The state lifecycle, in order: the SOURCE state's exit effects (`onExit`), then the
                // transition's own effects, then the DESTINATION state's entry effects (`onEnter`) —
                // portable-interaction/v1 state-exit/state-enter. Each is data, allow-listed, no code.
                $states = \is_array($machine['states'] ?? null) ? $machine['states'] : [];
                $onExit = \is_array($states[$current] ?? null) ? ($states[$current]['onExit'] ?? []) : [];
                $onEnter = \is_array($states[$transition['to']] ?? null) ? ($states[$transition['to']]['onEnter'] ?? []) : [];
                foreach ([$onExit, $transition['effects'] ?? [], $onEnter] as $effects) {
                    $bad = $this->applyEffects(\is_array($effects) ? $effects : [], $data, $emitted);
                    if ($bad !== null) {
                        // Closed allow-list: an effect no allow-list declares is refused, and the state does NOT move.
                        return new InteractionResult(
                            state: $request->state,
                            errors: ['effect' => "Effect type '{$bad}' is not allow-listed."],
                        );
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
     * Apply a list of allow-listed effects to `$data`/`$emitted` in place. Returns the offending type when an
     * effect is outside the allow-list (the caller refuses without advancing), or null when all applied.
     *
     * @param list<array<string, mixed>> $effects
     * @param array<string, mixed>       $data
     * @param list<array<string, mixed>> $emitted
     */
    private function applyEffects(array $effects, array &$data, array &$emitted): ?string
    {
        foreach ($effects as $effect) {
            $type = \is_string($effect['type'] ?? null) ? $effect['type'] : '';
            if (! \in_array($type, $this->allowedEffectTypes(), true)) {
                return $type;
            }
            // Read either the runtime's inline shape ({key,value,event}) or the canonical Action shape
            // ({target:{path}, params:{value|event}}) — the reconciliation to array+Action (greenhouse 0106).
            $key = \is_string($effect['key'] ?? null) ? $effect['key'] : (string) ($effect['target']['path'] ?? '');
            $value = \array_key_exists('value', $effect) ? $effect['value'] : ($effect['params']['value'] ?? null);
            $event = \is_string($effect['event'] ?? null) ? $effect['event'] : (string) ($effect['params']['event'] ?? '');
            if ($type === 'set-variable') {
                $data[$key] = $value;
            } elseif ($type === 'stamp') { // time as an input: read from the injected Clock, never date()
                $data[$key] = $this->clock->now();
            } else { // 'emit' — a named event the host may observe, echoed to the client, never code
                $emitted[] = ['type' => 'emit', 'event' => $event];
            }
        }

        return null;
    }

    /**
     * Resolve a portable-interaction/v1 Ref (`{kind?: 'data', path}`) into the state's data by dot-path — a
     * declared walk over data, never code. Only `kind: data` (or absent) is resolved; other kinds yield null.
     *
     * @param array<string, mixed> $ref
     * @param array<string, mixed> $data
     */
    private function resolveRef(array $ref, array $data): mixed
    {
        $kind = $ref['kind'] ?? 'data';
        if ($kind !== 'data') {
            return null;
        }
        $path = \is_string($ref['path'] ?? null) ? $ref['path'] : '';
        $cursor = $data;
        foreach (explode('.', $path) as $segment) {
            if (! \is_array($cursor) || ! \array_key_exists($segment, $cursor)) {
                return null;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /**
     * Select the transition for (state, event) from either the canonical ARRAY form [{from,on,to,when?,effects?}]
     * — which supports GUARD-FORKS (several transitions for the same state+event, the first whose guard holds
     * wins, greenhouse decisions/0106/evidence/0347) — or the runtime's nested-MAP form. Returns [transition, null]
     * or [null, errors].
     *
     * @param array<array-key, mixed> $transitions the array (list) or nested-map form
     * @param array<string, mixed>    $data
     *
     * @return array{0: array<string, mixed>|null, 1: array<string, string>|null}
     */
    private function selectTransition(array $transitions, string $current, string $event, array $data): array
    {
        if (array_is_list($transitions)) {
            $matched = false;
            foreach ($transitions as $t) {
                if (! \is_array($t) || ($t['from'] ?? null) !== $current || ($t['on'] ?? null) !== $event) {
                    continue;
                }
                $matched = true;
                if (! isset($t['when']) || $this->guardSatisfied($t['when'], $data)) {
                    return [$t, null];
                }
            }

            return $matched
                ? [null, ['guard' => "No '{$event}' transition from '{$current}' has a satisfied guard."]]
                : [null, ['transition' => "No '{$event}' transition from '{$current}'."]];
        }

        $t = $transitions[$current][$event] ?? null;
        if ($t === null) {
            return [null, ['transition' => "No '{$event}' transition from '{$current}'."]];
        }
        if (isset($t['when']) && ! $this->guardSatisfied($t['when'], $data)) {
            return [null, ['guard' => "The guard on '{$event}' from '{$current}' is not satisfied."]];
        }

        return [$t, null];
    }

    /**
     * Evaluate a declared guard (portable-interaction/v1 Condition) against the state. A leaf is `{var, op,
     * value}` over a state-data field with a CLOSED op set; a compound guard is `{all|any: [sub...]}` or
     * `{not: sub}` — a closed declarative tree. An unknown op fails closed (the transition is refused). No code.
     *
     * @param mixed                $when
     * @param array<string, mixed> $data
     */
    private function guardSatisfied($when, array $data): bool
    {
        if (! \is_array($when)) {
            return false;
        }

        // Compound guards are a CLOSED declarative tree: all/any/not over sub-conditions, leaves are
        // {var, op, value}. The recursion is bounded data, never code.
        if (\is_array($when['all'] ?? null)) {
            foreach ($when['all'] as $sub) {
                if (! $this->guardSatisfied($sub, $data)) {
                    return false;
                }
            }

            return true;
        }
        if (\is_array($when['any'] ?? null)) {
            foreach ($when['any'] as $sub) {
                if ($this->guardSatisfied($sub, $data)) {
                    return true;
                }
            }

            return false;
        }
        if (\array_key_exists('not', $when)) {
            return ! $this->guardSatisfied($when['not'], $data);
        }

        $op = \is_string($when['op'] ?? null) ? $when['op'] : 'truthy';
        // The left operand is a Ref (portable-interaction/v1 Ref{kind,path}) with dot-path resolution into the
        // state's data, or the flat `var` key (back-compat). The right operand is a literal `value` or another
        // Ref via `valueRef` (field-to-field comparison). Path resolution is declared data, never code.
        $actual = \is_array($when['ref'] ?? null)
            ? $this->resolveRef($when['ref'], $data)
            : ($data[\is_string($when['var'] ?? null) ? $when['var'] : ''] ?? null);
        $value = \is_array($when['valueRef'] ?? null)
            ? $this->resolveRef($when['valueRef'], $data)
            : ($when['value'] ?? null);
        $numeric = \is_numeric($actual) && \is_numeric($value);

        return match ($op) {
            'eq' => $actual == $value,
            'neq' => $actual != $value,
            'truthy' => (bool) $actual,
            'falsy' => ! $actual,
            'gt' => $numeric && $actual > $value,
            'gte' => $numeric && $actual >= $value,
            'lt' => $numeric && $actual < $value,
            'lte' => $numeric && $actual <= $value,
            default => false, // closed set: an unknown op is refused
        };
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
