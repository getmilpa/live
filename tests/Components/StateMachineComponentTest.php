<?php

declare(strict_types=1);

namespace Milpa\Live\Tests\Components;

use Milpa\Live\Components\StateMachineComponent;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\StateSnapshot;
use PHPUnit\Framework\TestCase;

/**
 * A closed declarative state machine over the wire (greenhouse decisions/0093): the transition table is data,
 * an undeclared event does not advance the state, and the owner (meta.principal) rides every transition.
 */
final class StateMachineComponentTest extends TestCase
{
    private function mount(array $props = [], string $principal = 'actor:rod'): StateSnapshot
    {
        return (new StateMachineComponent())->mount($props, new ComponentContext('sm-1', principal: $principal, route: '/live'));
    }

    private function advance(StateSnapshot $state, string $event): StateSnapshot
    {
        return (new StateMachineComponent())->handle(new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: $event,
            state: $state,
        ))->state;
    }

    public function testItStartsInTheInitialStateOwnedByTheMountActor(): void
    {
        $s = $this->mount();
        self::assertSame('queued', $s->data['state']);
        self::assertSame('actor:rod', $s->meta['principal'], 'the owner is born into the machine state');
    }

    public function testTheOwnerAdvancesThroughDeclaredTransitionsAndRidesEachOne(): void
    {
        $s = $this->mount();
        $s = $this->advance($s, 'start');
        self::assertSame('running', $s->data['state']);
        self::assertSame('actor:rod', $s->meta['principal'], 'the owner rides the transition');

        $s = $this->advance($s, 'finish');
        self::assertSame('done', $s->data['state']);
        self::assertSame('actor:rod', $s->meta['principal']);
    }

    public function testTheFailBranchIsReachable(): void
    {
        $s = $this->advance($this->advance($this->mount(), 'start'), 'fail');
        self::assertSame('failed', $s->data['state']);
    }

    public function testAnUndeclaredTransitionDoesNotAdvanceTheState(): void
    {
        $result = (new StateMachineComponent())->handle(new InteractionRequest(
            componentId: 'sm-1',
            componentName: 'state-machine',
            action: 'finish', // no 'finish' transition from 'queued'
            state: $this->mount(),
        ));
        self::assertSame('queued', $result->state->data['state'], 'the closed machine did not move');
        self::assertArrayHasKey('transition', $result->errors);
    }

    public function testAnInitialStatePropSeedsTheMachine(): void
    {
        self::assertSame('running', $this->mount(['initial' => 'running'])->data['state']);
        self::assertSame('queued', $this->mount(['initial' => 'nonsense'])->data['state'], 'an unknown initial falls back');
    }
}
