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

use Milpa\Events\InterceptionSlot;
use Milpa\Interfaces\Event\DeclaredEvents;
use Milpa\Interfaces\Event\EventDeclaration;
use Milpa\Interfaces\Event\MilpaEventDispatcherInterface;
use Milpa\Live\Components\Autocomplete\AutocompleteComponent;
use Milpa\Live\DataSource\ArrayDataSource;
use Milpa\Live\DataSource\InMemoryDataSourceRegistry;
use Milpa\Live\Events\LiveEventEmitter;
use Milpa\Live\Events\LiveEvents;
use Milpa\Live\Http\LiveHttpResponse;
use Milpa\Live\Tests\Fixtures\FixtureComponentRenderer;
use Milpa\Live\Tests\Fixtures\RecordingEventDispatcher;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\RenderRequest;
use Milpa\Live\ValueObjects\RenderTarget;
use PHPUnit\Framework\TestCase;

/**
 * The falsifier for greenhouse decisions/0228 in this package: every event
 * name {@see LiveEventEmitter} dispatches is declared, the declared set is
 * exactly the list below (a deleted or renamed declaration goes red), and
 * each declaration describes the payload the dispatcher really saw. The
 * control is the same code path on a dispatcher that does not implement
 * {@see DeclaredEvents}: it runs, dispatches the same names, and is asked
 * nothing.
 */
final class TheEmitterDeclaresEveryEventItDispatchesTest extends TestCase
{
    /** @var list<string> */
    private const EXPECTED_NAMES = [
        'component.mounting',
        'component.mounted',
        'component.handling',
        'component.handled',
        'component.rendering',
        'component.rendered',
        'live.request',
        'live.responded',
    ];

    public function testEveryNameTheEmitterDispatchesIsDeclaredAndTheDeclaredSetIsExactlyTheCatalogue(): void
    {
        $spy = $this->spy();

        $this->driveTheWholeLifecycleWith($spy);

        $declaredNames = array_map(static fn (EventDeclaration $d): string => $d->name, $spy->declarations);

        self::assertSame(self::EXPECTED_NAMES, $spy->dispatchedNames, 'The lifecycle drive must dispatch every name once, in lifecycle order.');
        self::assertSame([], array_diff($spy->dispatchedNames, $declaredNames), 'Every dispatched name must be declared — no undeclared dispatch.');
        self::assertSame(self::EXPECTED_NAMES, $declaredNames, 'The declared set must be exactly the catalogue: a deleted or renamed declaration goes red here.');
        self::assertSame(1, $spy->declareCalls, 'The emitter declares once per dispatcher, however many helpers it passes through.');
    }

    public function testEachDeclarationDescribesThePayloadTheDispatcherReallySaw(): void
    {
        $spy = $this->spy();

        $this->driveTheWholeLifecycleWith($spy);

        foreach ($spy->declarations as $declaration) {
            self::assertSame(LiveEventEmitter::class, $declaration->dispatchedBy, "{$declaration->name} is dispatched by the emitter.");
            self::assertNotSame('', trim($declaration->when), "{$declaration->name} says when it fires.");
            self::assertFalse($declaration->mutable, "{$declaration->name} carries a readonly VO; nothing here is mutable.");

            $payload = $spy->payloads[$declaration->name] ?? null;
            self::assertNotNull($payload, "{$declaration->name} was declared but never dispatched by the drive.");
            self::assertArrayHasKey($declaration->subjectKey, $payload, "{$declaration->name}: the payload carries the declared subject key.");
            self::assertNotNull($declaration->subjectType);
            self::assertInstanceOf($declaration->subjectType, $payload[$declaration->subjectKey], "{$declaration->name}: the subject is of the declared type.");

            $carriesSlot = ($payload['slot'] ?? null) instanceof InterceptionSlot;
            self::assertSame($carriesSlot, $declaration->interceptable, "{$declaration->name}: interceptable iff the payload carries an InterceptionSlot.");
        }
    }

    public function testDeclarationsAreBuiltFromTheSameConstantsTheEmitterDispatches(): void
    {
        $constants = (new \ReflectionClass(LiveEvents::class))->getConstants();
        $declaredNames = array_map(static fn (EventDeclaration $d): string => $d->name, LiveEvents::declarations());

        self::assertSame(array_values($constants), $declaredNames, 'Every name constant has exactly one declaration, in constant order.');
    }

    public function testTheEmitterDeclaresOncePerDispatcherInstanceNotOncePerProcess(): void
    {
        $first = $this->spy();
        $second = $this->spy();

        $this->driveTheWholeLifecycleWith($first);
        $this->driveTheWholeLifecycleWith($second);
        $this->driveTheWholeLifecycleWith($first);

        self::assertSame(1, $first->declareCalls);
        self::assertSame(1, $second->declareCalls);
        self::assertCount(count(self::EXPECTED_NAMES), $second->declarations);
    }

    public function testDeclaringEagerlyBeforeAnyDispatchIsTheSameDeclarationAndTheLazyPathAddsNothing(): void
    {
        $spy = $this->spy();

        LiveEventEmitter::declareTo($spy);
        self::assertSame(1, $spy->declareCalls);
        self::assertSame([], $spy->dispatchedNames, 'Declaring dispatches nothing.');

        $this->driveTheWholeLifecycleWith($spy);
        self::assertSame(1, $spy->declareCalls, 'The lazy path finds the dispatcher already declared to.');
        self::assertSame(self::EXPECTED_NAMES, $spy->dispatchedNames);
    }

    /**
     * CONTROL: a dispatcher that does not implement DeclaredEvents runs the
     * same lifecycle without error and sees the same names — it is asked
     * nothing, and `null` (no dispatcher) is asked nothing either.
     */
    public function testControlAPlainDispatcherRunsTheSameLifecycleAndIsAskedNothing(): void
    {
        $plain = new RecordingEventDispatcher();
        self::assertNotInstanceOf(DeclaredEvents::class, $plain);

        $this->driveTheWholeLifecycleWith($plain);
        LiveEventEmitter::declareTo($plain);
        LiveEventEmitter::declareTo(null);

        self::assertSame(self::EXPECTED_NAMES, array_column($plain->dispatched, 'name'));
    }

    /**
     * The real code paths of this package that dispatch, in lifecycle order:
     * a component's mount() and handle() (through the emitter), a renderer's
     * render() (through the emitter), and the HTTP pair the endpoint in
     * `milpa/live-web` calls — whose dispatch sites are here.
     */
    private function driveTheWholeLifecycleWith(?MilpaEventDispatcherInterface $dispatcher): void
    {
        $sources = new InMemoryDataSourceRegistry();
        $sources->register(new ArrayDataSource('customers.search', [
            ['value' => 'milpa', 'label' => 'Milpa Labs', 'search' => 'framework components'],
        ]));
        $component = new AutocompleteComponent($sources, $dispatcher);
        $context = new ComponentContext('declare-drive', route: '/lab/declare');
        $props = ['name' => 'customer', 'source' => 'customers.search'];

        $state = $component->mount($props, $context);
        $interaction = new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: 'search',
            state: $state,
            payload: ['query' => 'mil'],
        );
        $handled = $component->handle($interaction);

        (new FixtureComponentRenderer($dispatcher))->render($component, new RenderRequest(
            context: $context,
            props: $props,
            state: $handled->state,
            target: RenderTarget::HTML,
        ));

        LiveEventEmitter::liveRequest($dispatcher, $interaction, null);
        LiveEventEmitter::liveResponded($dispatcher, $interaction, LiveHttpResponse::ok(['ok' => true]), intercepted: false);
    }

    /**
     * A spy that is BOTH a dispatcher and a DeclaredEvents: records what was
     * declared to it, every name dispatched (first occurrence first) and the
     * first payload seen per name.
     *
     * @return MilpaEventDispatcherInterface&DeclaredEvents&object{declarations: list<EventDeclaration>, declareCalls: int, dispatchedNames: list<string>, payloads: array<string, array<string, mixed>>}
     */
    private function spy(): MilpaEventDispatcherInterface
    {
        return new class () implements MilpaEventDispatcherInterface, DeclaredEvents {
            /** @var list<EventDeclaration> */
            public array $declarations = [];
            public int $declareCalls = 0;
            /** @var list<string> */
            public array $dispatchedNames = [];
            /** @var array<string, array<string, mixed>> */
            public array $payloads = [];

            public function declare(EventDeclaration ...$events): void
            {
                ++$this->declareCalls;
                foreach ($events as $event) {
                    foreach ($this->declarations as $known) {
                        if ($known->name === $event->name) {
                            continue 2;
                        }
                    }
                    $this->declarations[] = $event;
                }
            }

            public function declared(): array
            {
                return $this->declarations;
            }

            public function dispatched(): array
            {
                return $this->dispatchedNames;
            }

            public function dispatch(string $eventName, array $payload = [], bool $async = false): void
            {
                if (!in_array($eventName, $this->dispatchedNames, true)) {
                    $this->dispatchedNames[] = $eventName;
                    $this->payloads[$eventName] = $payload;
                }
            }

            public function subscribe(string $eventName, callable $handler, int $priority = 0): void
            {
            }

            public function getSubscribers(string $eventName): array
            {
                return [];
            }

            public function hasSubscribers(string $eventName): bool
            {
                return false;
            }
        };
    }
}
