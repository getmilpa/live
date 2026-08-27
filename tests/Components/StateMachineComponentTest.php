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
 * A closed declarative state machine over the wire (greenhouse decisions/0093, effects: 0094): the transition
 * table AND each transition's effects are data, an undeclared event does not advance the state, an effect
 * outside the allow-list is refused, and the owner (meta.principal) rides every transition.
 */
final class StateMachineComponentTest extends TestCase
{
    private function mount(array $props = [], string $principal = 'actor:rod'): StateSnapshot
    {
        return (new StateMachineComponent())->mount($props, new ComponentContext('sm-1', principal: $principal, route: '/live'));
    }

    private function handle(StateSnapshot $state, string $event, ?StateMachineComponent $component = null): InteractionResult
    {
        return ($component ?? new StateMachineComponent())->handle(new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: $event,
            state: $state,
        ));
    }

    public function testItStartsInTheInitialStateOwnedByTheMountActor(): void
    {
        $s = $this->mount();
        self::assertSame('queued', $s->data['state']);
        self::assertSame('actor:rod', $s->meta['principal']);
    }

    public function testTheOwnerAdvancesAndTheTransitionFiresItsDeclaredEmitEffect(): void
    {
        $r = $this->handle($this->mount(), 'start');
        self::assertSame('running', $r->state->data['state']);
        self::assertSame('actor:rod', $r->state->meta['principal'], 'the owner rides the transition');
        self::assertSame([['type' => 'emit', 'event' => 'sm.started']], $r->effects, 'the transition fired its declared emit');
    }

    public function testASetVariableEffectWritesIntoTheStateData(): void
    {
        $running = $this->handle($this->mount(), 'start')->state;
        $r = $this->handle($running, 'finish');
        self::assertSame('done', $r->state->data['state']);
        self::assertSame('ok', $r->state->data['result'], 'the set-variable effect wrote result=ok into data');
        self::assertContains(['type' => 'emit', 'event' => 'sm.finished'], $r->effects);
    }

    public function testAnUndeclaredTransitionDoesNotAdvanceTheState(): void
    {
        $r = $this->handle($this->mount(), 'finish'); // no 'finish' from 'queued'
        self::assertSame('queued', $r->state->data['state']);
        self::assertArrayHasKey('transition', $r->errors);
    }

    public function testAnEffectOutsideTheAllowListIsRefusedAndTheStateDoesNotMove(): void
    {
        // a subclass whose transition declares an effect no allow-list covers
        $rogue = new class () extends StateMachineComponent {
            protected function transitions(): array
            {
                return ['queued' => ['start' => ['to' => 'running', 'effects' => [['type' => 'run-shell', 'cmd' => 'rm -rf /']]]]];
            }
        };
        $r = $this->handle($this->mount(), 'start', $rogue);
        self::assertSame('queued', $r->state->data['state'], 'the state did not move for a non-allow-listed effect');
        self::assertArrayHasKey('effect', $r->errors);
    }
}
