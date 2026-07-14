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

namespace Milpa\Live\Tests\Events;

use Milpa\Live\Components\Autocomplete\AutocompleteComponent;
use Milpa\Live\DataSource\ArrayDataSource;
use Milpa\Live\DataSource\InMemoryDataSourceRegistry;
use Milpa\Live\Events\ComponentHandledEvent;
use Milpa\Live\Events\ComponentHandlingEvent;
use Milpa\Live\Events\ComponentMountedEvent;
use Milpa\Live\Events\ComponentMountingEvent;
use Milpa\Live\Tests\Fixtures\RecordingEventDispatcher;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;
use PHPUnit\Framework\TestCase;

/**
 * Converted from `tests/smoke.php`'s "F4: milpa/live event-driven emit
 * points" section (lines ~1379-1571), items 1-3: these are pure
 * `milpa/live` core (Events + a component's own mount()/handle(), no
 * renderer involved) per the fase-B partition §5, so they port here
 * unmodified in substance. Items 4-5 (which exercise the emitter's
 * `withRendering()` slot through a concrete renderer) get their own class,
 * {@see LiveEventEmitterRenderingTest}, using a lightweight fixture
 * renderer instead of the lab's real (web-only) `AutocompleteHtmlRenderer`.
 */
final class LiveEventEmitterMountAndHandleTest extends TestCase
{
    private function sources(): InMemoryDataSourceRegistry
    {
        $sources = new InMemoryDataSourceRegistry();
        $sources->register(new ArrayDataSource('customers.search', [
            ['value' => 'acme', 'label' => 'Acme Studio', 'search' => 'agency design'],
            ['value' => 'milpa', 'label' => 'Milpa Labs', 'search' => 'framework components'],
        ]));

        return $sources;
    }

    /**
     * Item 1: component.mounting / component.mounted — plain PRE/POST, no
     * slot (mount has no interception seam in this catalog).
     */
    public function testMountDispatchesMountingThenMountedWithNoSlot(): void
    {
        $events = new RecordingEventDispatcher();
        $component = new AutocompleteComponent($this->sources(), $events);
        $context = new ComponentContext('f4-handling', route: '/lab/f4-handling');

        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search'], $context);

        $mounting = $events->named('component.mounting');
        self::assertCount(1, $mounting);
        self::assertInstanceOf(ComponentMountingEvent::class, $mounting[0]['payload']['event']);
        self::assertArrayNotHasKey('slot', $mounting[0]['payload'], 'Expected component.mounting to carry no InterceptionSlot.');

        $mounted = $events->named('component.mounted');
        self::assertCount(1, $mounted);
        self::assertInstanceOf(ComponentMountedEvent::class, $mounted[0]['payload']['event']);
        self::assertSame($state->componentId, $mounted[0]['payload']['event']->state->componentId);
    }

    /**
     * Item 2: component.handling short-circuit — a listener answers on the
     * component's behalf via InterceptionSlot::shortCircuit(); the
     * component's own handle() (the real search() against ArrayDataSource)
     * MUST NOT run, proven by a query the real data source would never match.
     */
    public function testHandlingShortCircuitReplacesTheRealHandleOutput(): void
    {
        $events = new RecordingEventDispatcher();
        $component = new AutocompleteComponent($this->sources(), $events);
        $context = new ComponentContext('f4-handling', route: '/lab/f4-handling');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search'], $context);

        $events->subscribe('component.handling', function (string $name, array $payload): void {
            $event = $payload['event'];
            self::assertInstanceOf(ComponentHandlingEvent::class, $event);
            if ($event->request->action === 'search') {
                $payload['slot']->shortCircuit(new InteractionResult(
                    state: new StateSnapshot(
                        componentId: $event->request->state->componentId,
                        componentName: $event->request->state->componentName,
                        version: $event->request->state->version,
                        data: array_merge($event->request->state->data, [
                            'items' => [['value' => 'intercepted', 'label' => 'Intercepted Result']],
                            'open' => true,
                        ]),
                        meta: $event->request->state->meta,
                    ),
                    meta: ['source' => 'component.handling-interceptor'],
                ));
            }
        });

        $handled = $component->handle(new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: 'search',
            state: $state,
            payload: ['query' => 'this-query-would-never-match-the-real-data-source'],
        ));

        self::assertSame('intercepted', $handled->state->data['items'][0]['value']);
        self::assertSame('component.handling-interceptor', $handled->meta['source'] ?? null);

        $handledEvents = $events->named('component.handled');
        self::assertCount(1, $handledEvents);
        self::assertInstanceOf(ComponentHandledEvent::class, $handledEvents[0]['payload']['event']);
        self::assertTrue(
            $handledEvents[0]['payload']['event']->intercepted,
            'Expected component.handled to be marked intercepted for a short-circuited handle().',
        );
    }

    /**
     * Item 3: component.handling pure veto — InterceptionSlot::stop()
     * without shortCircuit(); handle() still never runs, but the caller
     * gets an explicit vetoed InteractionResult (unchanged state, a
     * reported error) instead of a silent no-op.
     */
    public function testHandlingPureVetoLeavesStateUnchangedAndReportsAnError(): void
    {
        $events = new RecordingEventDispatcher();
        $events->subscribe('component.handling', static function (string $name, array $payload): void {
            $payload['slot']->stop();
        });

        $component = new AutocompleteComponent($this->sources(), $events);
        $context = new ComponentContext('f4-veto', route: '/lab/f4-veto');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search'], $context);

        $result = $component->handle(new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: 'search',
            state: $state,
            payload: ['query' => 'mil'],
        ));

        self::assertSame($state, $result->state, 'Expected a pure veto to leave state unchanged (the real search() never ran).');
        self::assertNotSame('', $result->errors['action'] ?? '', 'Expected a pure veto to surface via InteractionResult::$errors.');
    }

    /**
     * No dispatcher wired: identical construction minus the dispatcher must
     * run the REAL search() — the explicit, targeted no-dispatcher-unchanged
     * proof for mount()/handle() (item 5's renderer half lives in
     * {@see LiveEventEmitterRenderingTest}).
     */
    public function testNoDispatcherWiredRunsTheRealHandleLogic(): void
    {
        $component = new AutocompleteComponent($this->sources());
        $context = new ComponentContext('f4-no-dispatcher', route: '/lab/f4-handling');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search'], $context);

        $handled = $component->handle(new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: 'search',
            state: $state,
            payload: ['query' => 'mil'],
        ));

        self::assertSame('milpa', $handled->state->data['items'][0]['value']);
    }
}
