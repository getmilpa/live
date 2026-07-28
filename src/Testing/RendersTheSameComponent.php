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

namespace Milpa\Live\Testing;

use Milpa\Live\Components\Dashboard\MetricCardComponent;
use Milpa\Live\Contracts\Rendering\ComponentRendererInterface;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\RenderRequest;
use Milpa\Live\ValueObjects\RenderTarget;

/**
 * The conformance every surface renderer must satisfy, shipped by the package that owns the contract.
 *
 * The claim this exists to keep honest is the one the architecture is built on: **a component
 * definition is written once and projects on every surface, and adding a surface changes no
 * component.** Two renderers declaring the same list of component names does not establish it —
 * they can drift into rendering different things under matching labels, and nothing would say so.
 *
 * It lives here, and not in a test that imports both surfaces, because such a test cannot exist:
 * `milpa/live-web` and `milpa/live-tui` do not depend on each other and must not start. So the
 * contract package ships the suite and each surface runs it against its own implementation — the
 * same reason `contracts/` outranks any one skill that implements a station.
 *
 * What is asserted is deliberately surface-agnostic. Not "the HTML has a div" or "the frame has a
 * box" — those are each surface's business. Only that the *component's data reaches the output*,
 * which is the part that must be true everywhere or the promise is empty.
 */
trait RendersTheSameComponent
{
    /**
     * The renderer under test, and the target it claims.
     */
    abstract protected function rendererUnderTest(): ComponentRendererInterface;

    abstract protected function targetUnderTest(): RenderTarget;

    /**
     * Claims its own target and refuses the others.
     */
    public function test_it_declares_the_target_it_renders_for(): void
    {
        $renderer = $this->rendererUnderTest();
        $target = $this->targetUnderTest();

        self::assertTrue($renderer->supportsTarget($target));

        // And refuses the others. A renderer that claims every target would be picked by the
        // registry for surfaces it cannot actually serve, and the failure would surface as
        // mangled output rather than as a wiring mistake.
        foreach (RenderTarget::cases() as $other) {
            if ($other !== $target) {
                self::assertFalse($renderer->supportsTarget($other), "It must not claim {$other->value}.");
            }
        }
    }

    /**
     * Mounts when the caller did not, and hands back what it drew.
     */
    public function test_an_unmounted_component_is_mounted_and_its_snapshot_returned(): void
    {
        // The contract says a null state means mount first and return what was mounted. A renderer
        // that rendered without returning the snapshot would force the caller to mount again to
        // learn the state, and the second mount could differ from the one that was drawn.
        $result = $this->rendererUnderTest()->render(
            new MetricCardComponent(),
            $this->requestFor(['title' => 'Revenue', 'value' => '42']),
        );

        self::assertNotNull($result->state, 'A mounted render must hand back the snapshot it drew.');
        self::assertNotSame('', $result->output);
    }

    /**
     * The component's data survives the trip to this surface.
     */
    public function test_the_components_data_reaches_the_output(): void
    {
        // The whole promise, reduced to something both a browser and a terminal can be held to: a
        // metric card carrying 42 shows 42. How it is framed is each surface's business; that the
        // number survives the trip is not negotiable.
        $result = $this->rendererUnderTest()->render(
            new MetricCardComponent(),
            $this->requestFor(['title' => 'Revenue', 'value' => '4242', 'caption' => 'this quarter']),
        );

        self::assertStringContainsString('4242', $result->output);
        self::assertStringContainsString('Revenue', $result->output);
    }

    /**
     * Renders a definition written for no surface in particular.
     */
    public function test_it_renders_the_same_definition_nobody_adapted(): void
    {
        // MetricCardComponent comes from milpa/live and knows no surface. If projecting it required
        // a per-surface variant, this line would need one — and the architecture would be a
        // matrix of adapters rather than a contract.
        $definition = new MetricCardComponent();

        $result = $this->rendererUnderTest()->render($definition, $this->requestFor(['title' => 'X', 'value' => '1']));

        self::assertSame($this->targetUnderTest(), $result->format);
    }

    /**
     * Refuses a component outside its allow-list instead of drawing something empty.
     */
    public function test_a_component_it_does_not_support_is_refused_loudly(): void
    {
        $renderer = $this->rendererUnderTest();

        $this->expectException(\InvalidArgumentException::class);

        $renderer->render($this->unsupportedComponent(), $this->requestFor([]));
    }

    /**
     * @param array<string, mixed> $props
     */
    private function requestFor(array $props): RenderRequest
    {
        return new RenderRequest(
            context: new ComponentContext('conformance-1'),
            props: $props,
            target: $this->targetUnderTest(),
        );
    }

    private function unsupportedComponent(): \Milpa\Live\Contracts\Component\ComponentDefinitionInterface
    {
        return new class () implements \Milpa\Live\Contracts\Component\ComponentDefinitionInterface {
            public static function contract(): \Milpa\Live\ValueObjects\ComponentContract
            {
                return new \Milpa\Live\ValueObjects\ComponentContract(
                    name: 'not-a-component-any-surface-knows',
                    contractVersion: '1.0',
                );
            }

            public function mount(array $props, ComponentContext $context): \Milpa\Live\ValueObjects\StateSnapshot
            {
                // Unreachable by construction: a renderer must refuse an unknown component before
                // mounting it. Arriving here means the refusal came too late — the component was
                // already brought to life by something that then declined to draw it.
                throw new \LogicException('a renderer mounted a component it does not support');
            }

            public function handle(
                \Milpa\Live\ValueObjects\InteractionRequest $request,
            ): \Milpa\Live\ValueObjects\InteractionResult {
                throw new \LogicException('a renderer handled an interaction for a component it does not support');
            }
        };
    }
}
