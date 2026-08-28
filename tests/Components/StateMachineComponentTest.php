<?php

declare(strict_types=1);

namespace Milpa\Live\Tests\Components;

use Milpa\Live\Components\StateMachineComponent;
use Milpa\Interfaces\Clock;
use Milpa\Support\FixedClock;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;
use PHPUnit\Framework\TestCase;

/**
 * A closed declarative state machine over the wire (greenhouse decisions/0093, effects: 0094, props: 0095): the
 * machine — states, transitions AND effects — is data, and it can be DECLARED BY PROPS. The spec rides in the
 * SIGNED state's meta (tamper-proof) and handle() reads it back; an app-declared effect outside the closed
 * allow-list is still refused; a malformed spec falls back to the baked default; the owner rides every advance.
 */
final class StateMachineComponentTest extends TestCase
{
    private const DOOR = [
        'initial' => 'locked',
        'transitions' => [
            'locked' => ['unlock' => ['to' => 'unlocked', 'effects' => [['type' => 'emit', 'event' => 'door.unlocked']]]],
            'unlocked' => ['lock' => ['to' => 'locked']],
        ],
    ];

    private function mount(array $props = [], string $principal = 'actor:rod'): StateSnapshot
    {
        return (new StateMachineComponent())->mount($props, new ComponentContext('sm-1', principal: $principal, route: '/live'));
    }

    private function handle(StateSnapshot $state, string $event): InteractionResult
    {
        return (new StateMachineComponent())->handle(new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: $event,
            state: $state,
        ));
    }

    public function testTheBakedDefaultStillRunsWhenNoMachineIsGiven(): void
    {
        $r = $this->handle($this->mount(), 'start');
        self::assertSame('running', $r->state->data['state']);
        self::assertSame([['type' => 'emit', 'event' => 'sm.started']], $r->effects);
    }

    public function testAnAppDeclaredMachineRunsAndRidesInTheSignedMeta(): void
    {
        $s = $this->mount(['machine' => self::DOOR]);
        self::assertSame('locked', $s->data['state'], 'the app machine seeds its own initial');
        self::assertSame(self::DOOR['transitions'], $s->meta['machine']['transitions'], 'the machine rides in meta (signed)');
        self::assertSame('actor:rod', $s->meta['principal']);

        $r = $this->handle($s, 'unlock');
        self::assertSame('unlocked', $r->state->data['state'], 'the APP\'s transition ran, not the baked one');
        self::assertSame([['type' => 'emit', 'event' => 'door.unlocked']], $r->effects);

        $r2 = $this->handle($r->state, 'lock');
        self::assertSame('locked', $r2->state->data['state']);
    }

    public function testAnUndeclaredTransitionInAnAppMachineDoesNotAdvance(): void
    {
        $r = $this->handle($this->mount(['machine' => self::DOOR]), 'lock'); // no 'lock' from 'locked'
        self::assertSame('locked', $r->state->data['state']);
        self::assertArrayHasKey('transition', $r->errors);
    }

    public function testAnAppDeclaredEffectOutsideTheAllowListIsRefused(): void
    {
        $rogue = ['initial' => 'a', 'transitions' => [
            'a' => ['go' => ['to' => 'b', 'effects' => [['type' => 'run-shell', 'cmd' => 'rm -rf /']]]],
        ]];
        $r = $this->handle($this->mount(['machine' => $rogue]), 'go');
        self::assertSame('a', $r->state->data['state'], 'the state did not move for a non-allow-listed effect, even app-declared');
        self::assertArrayHasKey('effect', $r->errors);
    }

    public function testAMalformedSpecFallsBackToTheBakedDefault(): void
    {
        // transitions present but a transition has no string `to` → not well-formed
        $bad = ['initial' => 'x', 'transitions' => ['x' => ['go' => ['to' => 123]]]];
        $s = $this->mount(['machine' => $bad]);
        self::assertSame('queued', $s->data['state'], 'a malformed machine falls back to the baked default');
    }
    public function testTheGenericFireActionCarriesTheEventInThePayload(): void
    {
        // over the wire a props machine drives itself with the declared `fire` action + payload.event
        // (its own events are not static contract actions the authorizer could allow).
        $state = $this->mount(['machine' => self::DOOR]);
        $r = (new StateMachineComponent())->handle(new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: 'fire',
            state: $state,
            payload: ['event' => 'unlock'],
        ));
        self::assertSame('unlocked', $r->state->data['state']);
        self::assertContains(['type' => 'emit', 'event' => 'door.unlocked'], $r->effects);
        self::assertArrayHasKey('fire', StateMachineComponent::contract()->actions, 'fire is a declared action the authorizer allows');
        self::assertSame('event', StateMachineComponent::contract()->actions['fire']['scopeBy'], 'fire is scoped per-event by the authorizer (decisions/0096)');
    }
    public function testAGuardedTransitionFiresOnlyWhenItsDeclaredConditionHolds(): void
    {
        $gated = ['initial' => 'pending', 'transitions' => [
            'pending' => [
                'approve' => ['to' => 'pending', 'effects' => [['type' => 'set-variable', 'key' => 'ok', 'value' => true]]],
                'submit' => ['to' => 'sent', 'when' => ['var' => 'ok', 'op' => 'truthy']],
            ],
            'sent' => [],
        ]];
        $s = $this->mount(['machine' => $gated]);

        // submit before the guard holds → refused, no advance
        $blocked = $this->handle($s, 'submit');
        self::assertSame('pending', $blocked->state->data['state']);
        self::assertArrayHasKey('guard', $blocked->errors);

        // approve sets ok=true, then submit passes the guard
        $approved = $this->handle($s, 'approve')->state;
        self::assertTrue($approved->data['ok']);
        $sent = $this->handle($approved, 'submit');
        self::assertSame('sent', $sent->state->data['state'], 'the guard held → the transition fired');
    }

    public function testAnUnknownGuardOpFailsClosed(): void
    {
        $bad = ['initial' => 'a', 'transitions' => [
            'a' => ['go' => ['to' => 'b', 'when' => ['var' => 'x', 'op' => 'exec-shell', 'value' => 1]]],
        ]];
        $r = $this->handle($this->mount(['machine' => $bad]), 'go');
        self::assertSame('a', $r->state->data['state'], 'an unknown guard op is refused, not silently passed');
        self::assertArrayHasKey('guard', $r->errors);
    }
    public function testACompoundAllGuardNeedsEverySubconditionToHold(): void
    {
        $m = ['initial' => 'draft', 'transitions' => [
            'draft' => [
                'setok' => ['to' => 'draft', 'effects' => [['type' => 'set-variable', 'key' => 'ok', 'value' => true]]],
                'bump' => ['to' => 'draft', 'effects' => [['type' => 'set-variable', 'key' => 'count', 'value' => 2]]],
                'submit' => ['to' => 'sent', 'when' => ['all' => [
                    ['var' => 'ok', 'op' => 'truthy'],
                    ['var' => 'count', 'op' => 'gte', 'value' => 2],
                ]]],
            ],
            'sent' => [],
        ]];
        $s = $this->mount(['machine' => $m]);
        self::assertSame('draft', $this->handle($s, 'submit')->state->data['state'], 'neither sub holds → refused');

        $okOnly = $this->handle($s, 'setok')->state; // ok=true, count unset
        self::assertSame('draft', $this->handle($okOnly, 'submit')->state->data['state'], 'only one sub holds → refused');

        $both = $this->handle($this->handle($okOnly, 'bump')->state, 'submit');
        self::assertSame('sent', $both->state->data['state'], 'all subs hold → fires');
    }

    public function testCompoundAnyAndNotGuards(): void
    {
        $any = ['any' => [['var' => 'a', 'op' => 'truthy'], ['var' => 'b', 'op' => 'truthy']]];
        $m = ['initial' => 's', 'transitions' => [
            's' => [
                'seta' => ['to' => 's', 'effects' => [['type' => 'set-variable', 'key' => 'a', 'value' => true]]],
                'goany' => ['to' => 'done', 'when' => $any],
                'gonot' => ['to' => 'done', 'when' => ['not' => ['var' => 'a', 'op' => 'truthy']]],
            ],
            'done' => [],
        ]];
        // any: neither a nor b → refused; then a set → fires
        $s = $this->mount(['machine' => $m]);
        self::assertSame('s', $this->handle($s, 'goany')->state->data['state']);
        $withA = $this->handle($s, 'seta')->state;
        self::assertSame('done', $this->handle($withA, 'goany')->state->data['state'], 'any holds once a is set');
        // not: gonot fires while a is falsy, refused once a is set
        self::assertSame('done', $this->handle($s, 'gonot')->state->data['state'], 'not(a) holds while a is falsy');
        self::assertSame('s', $this->handle($withA, 'gonot')->state->data['state'], 'not(a) fails once a is truthy');
    }
    public function testAGuardCanCompareTwoStateFieldsViaValueRef(): void
    {
        $m = ['initial' => 'draft', 'transitions' => [
            'draft' => [
                'setc' => ['to' => 'draft', 'effects' => [['type' => 'set-variable', 'key' => 'count', 'value' => 3]]],
                'sett' => ['to' => 'draft', 'effects' => [['type' => 'set-variable', 'key' => 'threshold', 'value' => 2]]],
                'submit' => ['to' => 'sent', 'when' => ['ref' => ['path' => 'count'], 'op' => 'gte', 'valueRef' => ['path' => 'threshold']]],
            ],
            'sent' => [],
        ]];
        $s = $this->mount(['machine' => $m]);
        // count/threshold unset → not comparable → refused
        self::assertSame('draft', $this->handle($s, 'submit')->state->data['state']);
        $ready = $this->handle($this->handle($s, 'setc')->state, 'sett')->state; // count=3, threshold=2
        self::assertSame('sent', $this->handle($ready, 'submit')->state->data['state'], 'count>=threshold → fires (field-to-field)');
    }

    public function testAGuardRefResolvesADotPathIntoNestedState(): void
    {
        $state = new StateSnapshot('sm-1', 'state-machine', '1', ['state' => 'a', 'config' => ['limit' => 5]], [
            'principal' => 'actor:rod',
            'machine' => ['transitions' => ['a' => ['go' => ['to' => 'b', 'when' => ['ref' => ['path' => 'config.limit'], 'op' => 'gte', 'value' => 5]]]]],
        ]);
        self::assertSame('b', $this->handle($state, 'go')->state->data['state'], 'config.limit (5) >= 5 → fires');

        $low = new StateSnapshot('sm-1', 'state-machine', '1', ['state' => 'a', 'config' => ['limit' => 3]], $state->meta);
        self::assertSame('a', $this->handle($low, 'go')->state->data['state'], 'config.limit (3) < 5 → refused');
    }

    public function testTheFlatVarLeafStillWorks(): void
    {
        $m = ['initial' => 'p', 'transitions' => [
            'p' => ['ok' => ['to' => 'p', 'effects' => [['type' => 'set-variable', 'key' => 'flag', 'value' => true]]], 'go' => ['to' => 'q', 'when' => ['var' => 'flag', 'op' => 'truthy']]],
            'q' => [],
        ]];
        $s = $this->mount(['machine' => $m]);
        self::assertSame('p', $this->handle($s, 'go')->state->data['state'], 'flat var back-compat: refused while unset');
        self::assertSame('q', $this->handle($this->handle($s, 'ok')->state, 'go')->state->data['state']);
    }
    public function testEnteringAStateFiresItsOnEnterEffects(): void
    {
        $m = ['initial' => 'idle', 'transitions' => [
            'idle' => ['start' => ['to' => 'running', 'effects' => [['type' => 'emit', 'event' => 'started']]]],
            'running' => [],
        ], 'states' => [
            'running' => ['onEnter' => [['type' => 'set-variable', 'key' => 'entered', 'value' => true], ['type' => 'emit', 'event' => 'running.entered']]],
        ]];
        $r = $this->handle($this->mount(['machine' => $m]), 'start');
        self::assertSame('running', $r->state->data['state']);
        self::assertTrue($r->state->data['entered'], 'the destination state\'s onEnter set-variable fired');
        // both the transition emit and the onEnter emit are present
        self::assertContains(['type' => 'emit', 'event' => 'started'], $r->effects);
        self::assertContains(['type' => 'emit', 'event' => 'running.entered'], $r->effects, 'the onEnter emit fired on entry');
    }

    public function testAnOnEnterEffectOutsideTheAllowListIsRefused(): void
    {
        $m = ['initial' => 'a', 'transitions' => ['a' => ['go' => ['to' => 'b']]], 'states' => [
            'b' => ['onEnter' => [['type' => 'run-shell', 'cmd' => 'x']]],
        ]];
        $r = $this->handle($this->mount(['machine' => $m]), 'go');
        self::assertSame('a', $r->state->data['state'], 'a non-allow-listed onEnter effect refuses the transition');
        self::assertArrayHasKey('effect', $r->errors);
    }
    public function testTheLifecycleFiresInOrderOnExitTransitionOnEnter(): void
    {
        $m = ['initial' => 'idle', 'transitions' => [
            'idle' => ['start' => ['to' => 'running', 'effects' => [['type' => 'emit', 'event' => 'transition']]]],
            'running' => [],
        ], 'states' => [
            'idle' => ['onExit' => [['type' => 'emit', 'event' => 'idle.exit']]],
            'running' => ['onEnter' => [['type' => 'emit', 'event' => 'running.enter']]],
        ]];
        $r = $this->handle($this->mount(['machine' => $m]), 'start');
        self::assertSame('running', $r->state->data['state']);
        $events = array_map(static fn (array $e): string => $e['event'], $r->effects);
        self::assertSame(['idle.exit', 'transition', 'running.enter'], $events, 'onExit → transition → onEnter, in order');
    }

    public function testAnOnExitEffectOutsideTheAllowListIsRefused(): void
    {
        $m = ['initial' => 'a', 'transitions' => ['a' => ['go' => ['to' => 'b']]], 'states' => [
            'a' => ['onExit' => [['type' => 'run-shell', 'cmd' => 'x']]],
        ]];
        $r = $this->handle($this->mount(['machine' => $m]), 'go');
        self::assertSame('a', $r->state->data['state'], 'a non-allow-listed onExit refuses the transition');
        self::assertArrayHasKey('effect', $r->errors);
    }
    public function testAStampEffectWithAFixedClockIsDeterministic(): void
    {
        $m = ['initial' => 'a', 'transitions' => ['a' => ['go' => ['to' => 'b', 'effects' => [['type' => 'stamp', 'key' => 'at']]]]]];
        $component = new StateMachineComponent(null, new FixedClock('2026-08-27T00:00:00+00:00'));
        $mount = $component->mount(['machine' => $m], new ComponentContext('sm-1', principal: 'actor:rod'));

        $run = static fn (): array => $component->handle(new InteractionRequest(
            componentId: 'sm-1',
            componentName: 'state-machine',
            action: 'go',
            state: $mount,
        ))->state->data;

        $first = $run();
        $second = $run();
        self::assertSame('2026-08-27T00:00:00+00:00', $first['at'], 'the stamp read the injected clock, not date()');
        self::assertSame($first, $second, 'same inputs + same clock → identical state (deterministic replay)');
    }

    public function testTheStampGenuinelyReadsTheClockNotAConstant(): void
    {
        // a counting clock proves the stamp reflects the clock: two runs differ
        $counting = new class () implements Clock {
            private int $n = 0;
            public function now(): string
            {
                return 't' . (++$this->n);
            }

            public function instant(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('@' . $this->n);
            }
        };
        $m = ['initial' => 'a', 'transitions' => ['a' => ['go' => ['to' => 'b', 'effects' => [['type' => 'stamp', 'key' => 'at']]]]]];
        $component = new StateMachineComponent(null, $counting);
        $mount = $component->mount(['machine' => $m], new ComponentContext('sm-1'));
        $one = $component->handle(new InteractionRequest(componentId: 'sm-1', componentName: 'state-machine', action: 'go', state: $mount))->state->data['at'];
        $two = $component->handle(new InteractionRequest(componentId: 'sm-1', componentName: 'state-machine', action: 'go', state: $mount))->state->data['at'];
        self::assertNotSame($one, $two, 'the stamp reflects the clock (t1 then t2), so it is not a constant');
    }
    public function testTheCanonicalArrayFormRunsWithGuardForks(): void
    {
        // array+Action form (portable-interaction/v1.3): two `go` from `s` differ by guard — a fork the
        // nested-map could not express (greenhouse decisions/0106).
        $m = ['initial' => 's', 'states' => ['s', 'a', 'b'], 'transitions' => [
            ['from' => 's', 'on' => 'go', 'to' => 'a', 'when' => ['ref' => ['kind' => 'data', 'path' => 'x'], 'op' => 'truthy'],
             'effects' => [['type' => 'emit', 'params' => ['event' => 'went.a']]]],
            ['from' => 's', 'on' => 'go', 'to' => 'b', 'when' => ['ref' => ['kind' => 'data', 'path' => 'x'], 'op' => 'falsy']],
        ]];
        // x falsy → fork to b
        $s0 = $this->mount(['machine' => $m]);
        self::assertSame('b', $this->handle($s0, 'go')->state->data['state'], 'falsy guard forks to b');

        // x truthy → fork to a, with its Action-shaped emit
        $sx = new StateSnapshot($s0->componentId, $s0->componentName, $s0->version, ['state' => 's', 'x' => true], $s0->meta);
        $r = $this->handle($sx, 'go');
        self::assertSame('a', $r->state->data['state'], 'truthy guard forks to a');
        self::assertContains(['type' => 'emit', 'event' => 'went.a'], $r->effects, 'canonical Action-shaped emit fired');
    }

    public function testTheCanonicalArrayFormLifecycleAndStamp(): void
    {
        $m = ['initial' => 'idle', 'transitions' => [
            ['from' => 'idle', 'on' => 'start', 'to' => 'running', 'effects' => [['type' => 'stamp', 'target' => ['kind' => 'data', 'path' => 'at']]]],
        ], 'states' => ['running' => ['onEnter' => [['type' => 'set-variable', 'target' => ['kind' => 'data', 'path' => 'entered'], 'params' => ['value' => true]]]]]];
        $c = new StateMachineComponent(null, new FixedClock('2026-08-27T00:00:00+00:00'));
        $r = $c->handle(new InteractionRequest(
            componentId: 'sm-1',
            componentName: 'state-machine',
            action: 'start',
            state: $c->mount(['machine' => $m], new ComponentContext('sm-1', principal: 'actor:rod'))
        ));
        self::assertSame('running', $r->state->data['state']);
        self::assertSame('2026-08-27T00:00:00+00:00', $r->state->data['at'], 'stamp Action wrote the injected clock into target.path');
        self::assertTrue($r->state->data['entered'], 'onEnter Action fired on entry');
    }
}
