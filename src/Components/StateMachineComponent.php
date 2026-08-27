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
 * (greenhouse decisions/0087) made executable in the runtime: the states, the initial state and the
 * transition table are DATA, and {@see handle()} is a pure lookup in that table — the reducer IS the table,
 * with no per-transition code. An event that no transition declares does not advance the state (a closed
 * union, not an open handler); an action the contract does not declare never reaches here (the endpoint's
 * authorizer refuses it first). The owner rides every transition: `meta.principal` is preserved across each
 * advance, so a machine advances only for the actor it was mounted for (greenhouse decisions/0093).
 */
final class StateMachineComponent extends AbstractDashboardComponent
{
    private const NAME = 'state-machine';

    private const VERSION = '0.1.0';

    private const INITIAL = 'queued';

    /**
     * The whole behaviour, as data: from-state => (event => to-state). A reviewer reads every reachable
     * state and every legal advance; extending it is a version bump, never code.
     *
     * @var array<string, array<string, string>>
     */
    private const TRANSITIONS = [
        'queued' => ['start' => 'running'],
        'running' => ['finish' => 'done', 'fail' => 'failed'],
        'done' => [],
        'failed' => [],
    ];

    /** The machine's contract: a single `state` field and the declared events; the authorizer allow-lists these. */
    public static function contract(): ComponentContract
    {
        return new ComponentContract(
            name: self::NAME,
            contractVersion: self::VERSION,
            summary: 'A closed declarative state machine: states and transitions are data, the reducer is the table.',
            stateSchema: ['state' => ['type' => 'string']],
            actions: ['start' => [], 'finish' => [], 'fail' => []],
        );
    }

    /**
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    protected function initialData(array $props): array
    {
        $initial = \is_string($props['initial'] ?? null) && $props['initial'] !== '' ? $props['initial'] : self::INITIAL;

        return ['state' => \array_key_exists($initial, self::TRANSITIONS) ? $initial : self::INITIAL];
    }

    /** One event: advance along the declared transition, or refuse without advancing when none is declared. */
    public function handle(InteractionRequest $request): InteractionResult
    {
        return LiveEventEmitter::withHandling(
            $this->dispatcher,
            $request,
            function () use ($request): InteractionResult {
                $current = \is_string($request->state->data['state'] ?? null) ? $request->state->data['state'] : self::INITIAL;
                $next = self::TRANSITIONS[$current][$request->action] ?? null;

                if ($next === null) {
                    // Closed machine: an event no transition declares does not move the state.
                    return new InteractionResult(
                        state: $request->state,
                        errors: ['transition' => "No '{$request->action}' transition from '{$current}'."],
                    );
                }

                return new InteractionResult(
                    state: new StateSnapshot(
                        componentId: $request->state->componentId,
                        componentName: $request->state->componentName,
                        version: $request->state->version,
                        data: ['state' => $next],
                        // the owner rides the transition: meta.principal is preserved across every advance
                        meta: $request->state->meta,
                    ),
                );
            },
        );
    }
}
