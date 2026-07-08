<?php

/**
 * This file is part of Milpa Live — the render-target-agnostic live component core of the Milpa PHP framework.
 *
 * (c) TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\Tests\Events;

use Milpa\Live\Components\Autocomplete\AutocompleteComponent;
use Milpa\Live\DataSource\InMemoryDataSourceRegistry;
use Milpa\Live\Events\ComponentRenderedEvent;
use Milpa\Live\Events\ComponentRenderingEvent;
use Milpa\Live\Tests\Fixtures\FixtureComponentRenderer;
use Milpa\Live\Tests\Fixtures\RecordingEventDispatcher;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\RenderRequest;
use Milpa\Live\ValueObjects\RenderResult;
use Milpa\Live\ValueObjects\RenderTarget;
use PHPUnit\Framework\TestCase;

/**
 * Converted from `tests/smoke.php`'s F4 section, items 4-5 (lines
 * ~1516-1571): the `component.rendering` short-circuit mechanics and the
 * no-dispatcher-unchanged proof for rendering. The lab exercises these
 * through the real `AutocompleteHtmlRenderer` (a `milpa/live-web` concern);
 * per the fase-B partition §5, the *mechanics* (slot short-circuit/
 * passthrough) belong here in `milpa/live`'s own suite using a lightweight
 * stand-in renderer ({@see FixtureComponentRenderer}) — `milpa/live-web`
 * gets its own analogous integration test using the real HTML renderer.
 */
final class LiveEventEmitterRenderingTest extends TestCase
{
    public function testRenderingShortCircuitReplacesTheRealRendererOutput(): void
    {
        $events = new RecordingEventDispatcher();
        $component = new AutocompleteComponent(new InMemoryDataSourceRegistry());
        $context = new ComponentContext('f4-render', route: '/lab/f4-render');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search'], $context);

        $events->subscribe('component.rendering', function (string $name, array $payload): void {
            $event = $payload['event'];
            self::assertInstanceOf(ComponentRenderingEvent::class, $event);
            if ($event->componentName === 'autocomplete') {
                $payload['slot']->shortCircuit(new RenderResult(
                    output: '<!-- decorated-by-plugin --><div data-f4-decorated="true"></div>',
                    state: $event->request->state,
                    format: $event->request->target,
                ));
            }
        });

        $renderer = new FixtureComponentRenderer($events);
        $rendered = $renderer->render($component, new RenderRequest(
            context: $context,
            props: ['name' => 'customer', 'source' => 'customers.search'],
            state: $state,
            target: RenderTarget::HTML,
        ));

        self::assertStringContainsString('data-f4-decorated="true"', $rendered->output);
        self::assertStringNotContainsString('fixture-rendered:', $rendered->output, 'Expected the real renderer output to never run once rendering was short-circuited.');

        $renderedEvents = $events->named('component.rendered');
        self::assertCount(1, $renderedEvents);
        self::assertInstanceOf(ComponentRenderedEvent::class, $renderedEvents[0]['payload']['event']);
        self::assertTrue($renderedEvents[0]['payload']['event']->intercepted);
    }

    public function testNoDispatcherWiredRunsTheRealRendererOutputUnmodified(): void
    {
        $component = new AutocompleteComponent(new InMemoryDataSourceRegistry());
        $context = new ComponentContext('f4-render-plain', route: '/lab/f4-render');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search'], $context);

        $renderer = new FixtureComponentRenderer();
        $rendered = $renderer->render($component, new RenderRequest(
            context: $context,
            props: ['name' => 'customer', 'source' => 'customers.search'],
            state: $state,
            target: RenderTarget::HTML,
        ));

        self::assertStringContainsString('fixture-rendered:' . $state->componentId, $rendered->output);
        self::assertStringNotContainsString('data-f4-decorated', $rendered->output);
    }
}
