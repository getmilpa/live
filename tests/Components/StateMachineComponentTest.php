<?php

declare(strict_types=1);

namespace Milpa\Live\Tests\Components;

use Milpa\Live\Components\StateMachineComponent;
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
}
