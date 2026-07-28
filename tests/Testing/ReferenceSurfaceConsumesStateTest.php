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

namespace Milpa\Live\Tests\Testing;

use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Testing\SurfacesConsumeStateNeverProduceIt;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\ComponentContract;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;
use PHPUnit\Framework\TestCase;

/**
 * The falsifier, exercised at home against a surface that behaves and one that does not.
 *
 * Both directions, because an assertion nobody has watched fail is a decoration. The obedient
 * surface hands back exactly what the component produced; the overreaching one adds a single field
 * of its own, which is the smallest possible version of the failure the law is about — and it must
 * be caught.
 */
final class ReferenceSurfaceConsumesStateTest extends TestCase
{
    use SurfacesConsumeStateNeverProduceIt;

    private function component(): ComponentDefinitionInterface
    {
        return new class () implements ComponentDefinitionInterface {
            public static function contract(): ComponentContract
            {
                return new ComponentContract(name: 'counter', contractVersion: '1.0');
            }

            public function mount(array $props, ComponentContext $context): StateSnapshot
            {
                return new StateSnapshot($context->componentId, 'counter', '1.0', ['n' => 0]);
            }

            public function handle(InteractionRequest $request): InteractionResult
            {
                return new InteractionResult(new StateSnapshot(
                    $request->componentId,
                    'counter',
                    '1.0',
                    ['n' => (int) ($request->state->data['n'] ?? 0) + 1],
                ));
            }
        };
    }

    private function request(): InteractionRequest
    {
        return new InteractionRequest(
            componentId: 'c1',
            componentName: 'counter',
            action: 'increment',
            state: new StateSnapshot('c1', 'counter', '1.0', ['n' => 4]),
        );
    }

    public function test_a_surface_that_only_relays_the_components_state_passes(): void
    {
        $component = $this->component();
        $request = $this->request();

        // The whole obligation of a shell, in one line: hand over what came back.
        $relayed = $component->handle($request)->state;

        $this->assertSurfaceOnlyConsumedState($component, $request, $relayed);
    }

    public function test_the_same_state_rebuilt_from_the_wire_still_passes(): void
    {
        // Rehydration is not authorship. A surface that serialized the snapshot, sent it and built
        // it again must not be accused of producing state, or every networked surface fails and
        // the law becomes something people route around.
        $component = $this->component();
        $request = $this->request();
        $produced = $component->handle($request)->state;

        $rebuilt = new StateSnapshot(
            $produced->componentId,
            $produced->componentName,
            $produced->version,
            $produced->data,
            $produced->meta,
        );

        $this->assertSurfaceOnlyConsumedState($component, $request, $rebuilt);
    }

    public function test_a_surface_that_adds_one_field_of_its_own_is_caught(): void
    {
        $component = $this->component();
        $request = $this->request();
        $produced = $component->handle($request)->state;

        $overreaching = new StateSnapshot(
            $produced->componentId,
            $produced->componentName,
            $produced->version,
            $produced->data + ['rendered_at' => 'now'],
        );

        // The smallest overreach there is: one extra key, plausibly useful, authored by the shell.
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $this->assertSurfaceOnlyConsumedState($component, $request, $overreaching);
    }

    public function test_a_surface_that_changes_a_value_is_caught(): void
    {
        $component = $this->component();
        $request = $this->request();
        $produced = $component->handle($request)->state;

        $amended = new StateSnapshot(
            $produced->componentId,
            $produced->componentName,
            $produced->version,
            ['n' => 99],
        );

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $this->assertSurfaceOnlyConsumedState($component, $request, $amended);
    }
}
