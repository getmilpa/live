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

namespace Milpa\Live\Tests\Runtime;

use Milpa\Live\Components\Autocomplete\AutocompleteComponent;
use Milpa\Live\Components\Form\InputComponent;
use Milpa\Live\Components\Form\TextareaComponent;
use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Contracts\Component\ComponentRegistryInterface;
use Milpa\Live\DataSource\ArrayDataSource;
use Milpa\Live\DataSource\InMemoryDataSourceRegistry;
use Milpa\Live\Runtime\ComponentNameConflictException;
use Milpa\Live\Runtime\CompositeComponentRegistry;
use Milpa\Live\Runtime\InMemoryComponentRegistry;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\ComponentContract;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;
use PHPUnit\Framework\TestCase;

/**
 * One registry over labelled layers (greenhouse decisions/0211): first layer wins, shadowing is never silent,
 * writes go to the one writable layer.
 */
final class CompositeComponentRegistryTest extends TestCase
{
    public function testItResolvesTheFirstLayerThatHasTheName(): void
    {
        $host = new InMemoryComponentRegistry();
        $hostTextarea = new TextareaComponent();
        $host->register('textarea', $hostTextarea);

        $plugin = new InMemoryComponentRegistry();
        $plugin->register('input', new InputComponent());
        $plugin->register('textarea', $hostTextarea); // the same instance in two layers is fine

        $composite = new CompositeComponentRegistry(['host' => $host, 'plugin' => $plugin]);

        self::assertTrue($composite->has('textarea'));
        self::assertTrue($composite->has('input'));
        self::assertFalse($composite->has('missing'));
        self::assertSame($hostTextarea, $composite->get('textarea'));
        self::assertSame($plugin->get('input'), $composite->get('input'));
        self::assertSame(['host', 'plugin'], $composite->labels());
    }

    public function testGetOnANameNoLayerHasThrows(): void
    {
        $composite = new CompositeComponentRegistry(['host' => new InMemoryComponentRegistry()]);

        $this->expectException(\RuntimeException::class);
        $composite->get('missing');
    }

    public function testNamesIsTheUnionInLayerOrderEachNameOnce(): void
    {
        $host = new InMemoryComponentRegistry();
        $textarea = new TextareaComponent();
        $host->register('textarea', $textarea);
        $host->register('input', new InputComponent());

        $plugin = new InMemoryComponentRegistry();
        $plugin->register('autocomplete', new AutocompleteComponent(new InMemoryDataSourceRegistry()));
        $plugin->register('textarea', $textarea); // the host's instance, reused: one definition under one name

        $composite = new CompositeComponentRegistry(['host' => $host, 'plugin' => $plugin]);

        self::assertSame(['textarea', 'input', 'autocomplete'], $composite->names());
    }

    public function testTheSameNameBoundToDifferentClassesIsAConflictNamingBothLayers(): void
    {
        $a = new InMemoryComponentRegistry();
        $a->register('field', new TextareaComponent());
        $b = new InMemoryComponentRegistry();
        $b->register('field', new InputComponent());

        try {
            new CompositeComponentRegistry(['plugin-a' => $a, 'plugin-b' => $b]);
            self::fail('expected a conflict');
        } catch (ComponentNameConflictException $e) {
            self::assertSame('field', $e->component);
            self::assertSame('plugin-a', $e->firstLayer);
            self::assertSame('plugin-b', $e->secondLayer);
            self::assertStringContainsString('"field"', $e->getMessage());
            self::assertStringContainsString('"plugin-a"', $e->getMessage());
            self::assertStringContainsString('"plugin-b"', $e->getMessage());
        }
    }

    public function testTwoInstancesOfOneClassWithDifferentStateAreAConflict(): void
    {
        $hostSources = new InMemoryDataSourceRegistry();
        $hostSources->register(new ArrayDataSource('customers.search', [['value' => 'acme', 'label' => 'Acme']]));
        $a = new InMemoryComponentRegistry();
        $a->register('autocomplete', new AutocompleteComponent($hostSources));
        $b = new InMemoryComponentRegistry();
        $b->register('autocomplete', new AutocompleteComponent(new InMemoryDataSourceRegistry()));

        $this->expectException(ComponentNameConflictException::class);
        $this->expectExceptionMessage('"autocomplete"');
        new CompositeComponentRegistry(['host' => $a, 'guest' => $b]);
    }

    /** The rule is identity or statelessness, never a structural compare: a stateful pair conflicts even when its state would compare equal. */
    public function testTwoInstancesOfAStatefulClassAreAConflictEvenWhenTheirStateWouldCompareEqual(): void
    {
        $a = new InMemoryComponentRegistry();
        $a->register('autocomplete', new AutocompleteComponent(new InMemoryDataSourceRegistry()));
        $b = new InMemoryComponentRegistry();
        $b->register('autocomplete', new AutocompleteComponent(new InMemoryDataSourceRegistry()));

        $this->expectException(ComponentNameConflictException::class);
        $this->expectExceptionMessage('"autocomplete"');
        new CompositeComponentRegistry(['host' => $a, 'guest' => $b]);
    }

    /**
     * Stateless means no instance property at all. The shipped form components are NOT stateless (they hold a
     * dispatcher, see {@see testTwoShippedTextareasAreAConflict()}), so the specimen is a property-less class.
     */
    public function testTwoInstancesOfAStatelessClassAreOneDefinition(): void
    {
        $stateless = static fn (): ComponentDefinitionInterface => new class () implements ComponentDefinitionInterface {
            public static function contract(): ComponentContract
            {
                return new ComponentContract(name: 'badge', contractVersion: '1', actions: []);
            }

            public function mount(array $props, ComponentContext $context): StateSnapshot
            {
                return new StateSnapshot($context->componentId, 'badge', '1', ['label' => $props['label'] ?? '']);
            }

            public function handle(InteractionRequest $request): InteractionResult
            {
                return new InteractionResult($request->state);
            }
        };
        $a = new InMemoryComponentRegistry();
        $a->register('badge', $stateless());
        $b = new InMemoryComponentRegistry();
        $b->register('badge', $stateless());
        self::assertNotSame($a->get('badge'), $b->get('badge'), 'two instances');

        $composite = new CompositeComponentRegistry(['host' => $a, 'guest' => $b]);

        self::assertSame($a->get('badge'), $composite->get('badge'), 'first layer wins');
    }

    /**
     * A shipped form component holds a dispatcher (an instance property), so two `new TextareaComponent()` under
     * one name are two definitions — the plugin reuses the host's instance or picks its own name.
     */
    public function testTwoShippedTextareasAreAConflict(): void
    {
        $a = new InMemoryComponentRegistry();
        $a->register('textarea', new TextareaComponent());
        $b = new InMemoryComponentRegistry();
        $b->register('textarea', new TextareaComponent());

        $this->expectException(ComponentNameConflictException::class);
        $this->expectExceptionMessage('"textarea"');
        new CompositeComponentRegistry(['host' => $a, 'guest' => $b]);
    }

    /**
     * Positive control for the rule above: a component whose object graph has a cycle (a collaborator holding the
     * component back) took a deep `==` into "Nesting level too deep" — an uncatchable fatal at construction, no
     * component named, no layer named. It is judged like any other stateful pair.
     */
    public function testAComponentWhoseGraphHasACycleIsJudgedAConflictNotAFatal(): void
    {
        $cyclic = static function (): ComponentDefinitionInterface {
            $component = new class () implements ComponentDefinitionInterface {
                public ?object $collaborator = null;

                public static function contract(): ComponentContract
                {
                    return new ComponentContract(name: 'cyclic', contractVersion: '1', actions: []);
                }

                public function mount(array $props, ComponentContext $context): StateSnapshot
                {
                    return new StateSnapshot($context->componentId, 'cyclic', '1', []);
                }

                public function handle(InteractionRequest $request): InteractionResult
                {
                    return new InteractionResult($request->state);
                }
            };
            $component->collaborator = new class ($component) {
                public function __construct(public readonly object $listener)
                {
                }
            };

            return $component;
        };
        $a = new InMemoryComponentRegistry();
        $a->register('cyclic', $cyclic());
        $b = new InMemoryComponentRegistry();
        $b->register('cyclic', $cyclic());

        $this->expectException(ComponentNameConflictException::class);
        $this->expectExceptionMessage('"cyclic"');
        new CompositeComponentRegistry(['host' => $a, 'guest' => $b]);
    }

    public function testNamesAreStringsEvenWhenANameIsNumeric(): void
    {
        $host = new InMemoryComponentRegistry();
        $host->register('2024', new TextareaComponent());
        $composite = new CompositeComponentRegistry(['host' => $host]);

        self::assertSame(['2024'], $composite->names());
    }

    public function testRegisterWritesToTheWritableLayer(): void
    {
        $host = new InMemoryComponentRegistry();
        $plugin = new InMemoryComponentRegistry();
        $composite = new CompositeComponentRegistry(['host' => $host, 'plugin' => $plugin], writable: 'plugin');

        $component = new TextareaComponent();
        $composite->register('textarea', $component);

        self::assertTrue($plugin->has('textarea'));
        self::assertFalse($host->has('textarea'));
        self::assertSame($component, $composite->get('textarea'));
        self::assertSame(['textarea'], $composite->names());
    }

    public function testRegisterIntoTheWritableLayerStillRefusesToShadowAnotherLayer(): void
    {
        $host = new InMemoryComponentRegistry();
        $host->register('field', new TextareaComponent());
        $composite = new CompositeComponentRegistry(['host' => $host, 'plugin' => new InMemoryComponentRegistry()], writable: 'plugin');

        $this->expectException(ComponentNameConflictException::class);
        $composite->register('field', new InputComponent());
    }

    public function testWithoutAWritableLayerRegisterThrows(): void
    {
        $composite = new CompositeComponentRegistry(['host' => new InMemoryComponentRegistry()]);

        $this->expectException(\LogicException::class);
        $composite->register('textarea', new TextareaComponent());
    }

    public function testAWritableLabelThatNamesNoLayerIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CompositeComponentRegistry(['host' => new InMemoryComponentRegistry()], writable: 'plugin');
    }

    public function testNoLayersIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CompositeComponentRegistry([]);
    }

    public function testAnOpaqueLayerResolvesButIsNotEnumerated(): void
    {
        $textarea = new TextareaComponent();
        $opaque = new class ($textarea) implements ComponentRegistryInterface {
            public function __construct(private readonly ComponentDefinitionInterface $only)
            {
            }

            public function has(string $name): bool
            {
                return $name === 'textarea';
            }

            public function get(string $name): ComponentDefinitionInterface
            {
                return $this->only;
            }

            public function register(string $name, ComponentDefinitionInterface $component): void
            {
            }
        };
        $listing = new InMemoryComponentRegistry();
        $listing->register('input', new InputComponent());

        $composite = new CompositeComponentRegistry(['opaque' => $opaque, 'listing' => $listing]);

        self::assertSame($textarea, $composite->get('textarea'));
        self::assertSame(['input'], $composite->names(), 'an opaque layer cannot be listed');
    }
}
