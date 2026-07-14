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

namespace Milpa\Live\Tests\Fixtures;

use Milpa\Interfaces\Event\MilpaEventDispatcherInterface;
use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Contracts\Rendering\ComponentRendererInterface;
use Milpa\Live\Events\LiveEventEmitter;
use Milpa\Live\ValueObjects\RenderRequest;
use Milpa\Live\ValueObjects\RenderResult;
use Milpa\Live\ValueObjects\RenderTarget;

/**
 * A minimal, dependency-free {@see ComponentRendererInterface} stand-in used
 * to exercise {@see LiveEventEmitter::withRendering()}'s slot mechanics
 * (short-circuit / veto / passthrough) without pulling in a real HTML/TUI
 * renderer (both of which live in `milpa/live-web`, not this core package).
 * Its "real" output is a deterministic marker string derived from the
 * mounted/given state so tests can assert the real path ran (or didn't).
 */
final class FixtureComponentRenderer implements ComponentRendererInterface
{
    public function __construct(
        private readonly ?MilpaEventDispatcherInterface $dispatcher = null,
    ) {
    }

    public function supportsTarget(RenderTarget $target): bool
    {
        return $target === RenderTarget::HTML;
    }

    public function render(ComponentDefinitionInterface $component, RenderRequest $request): RenderResult
    {
        $componentName = $component::contract()->name;

        return LiveEventEmitter::withRendering(
            $this->dispatcher,
            $componentName,
            $request,
            function () use ($component, $request): RenderResult {
                $state = $request->state ?? $component->mount($request->props, $request->context);

                return new RenderResult(
                    output: 'fixture-rendered:' . $state->componentId,
                    state: $state,
                    format: $request->target,
                );
            },
        );
    }
}
