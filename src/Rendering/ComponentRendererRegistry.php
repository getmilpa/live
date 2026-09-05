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

namespace Milpa\Live\Rendering;

use Milpa\Live\Contracts\Rendering\ComponentRendererInterface;
use Milpa\Live\Contracts\Rendering\ComponentRendererRegistryInterface;
use Milpa\Live\ValueObjects\RenderTarget;

/**
 * The default in-memory {@see ComponentRendererRegistryInterface}:
 * resolves a renderer by target, preferring the most recently
 * {@see register()}ed renderer that {@see ComponentRendererInterface::supportsTarget()}
 * it.
 *
 * It is also the pair of a {@see \Milpa\Live\Runtime\CompositeComponentRegistry}
 * (greenhouse decisions/0211): a renderer may be registered FOR a component
 * name ({@see registerFor()}), and {@see resolveFor()} answers "who renders
 * this component at this target" — the per-name renderer when it supports the
 * target, else nothing. The target-wide {@see resolve()} is a different
 * question and is deliberately NOT its fallback: every shipped HTML renderer
 * is single-family and throws for the rest, so handing a name to whatever
 * renderer happens to support the target turns a missing registration into an
 * uncaught exception in the endpoint instead of a null. A host with a general
 * renderer registers it for each name it serves. That is how one live endpoint
 * re-renders every plugin's components without a hand-wired name → renderer
 * array — the registry answers exactly what the array did.
 */
final class ComponentRendererRegistry implements ComponentRendererRegistryInterface
{
    /**
     * @var array<int, ComponentRendererInterface>
     */
    private array $renderers = [];

    /**
     * @var array<string, array<int, ComponentRendererInterface>> component name => renderers, most recent first
     */
    private array $byComponent = [];

    /** Registers `$renderer`, taking precedence over any already-registered renderer for the same target. */
    public function register(ComponentRendererInterface $renderer): void
    {
        array_unshift($this->renderers, $renderer);
    }

    /**
     * Registers `$renderer` for the component named `$componentName` — it takes precedence, for that
     * name, over any target-wide renderer and over any renderer registered for the name earlier.
     */
    public function registerFor(string $componentName, ComponentRendererInterface $renderer): void
    {
        $this->byComponent[$componentName] ??= [];
        array_unshift($this->byComponent[$componentName], $renderer);
    }

    /** Returns the most recently registered renderer that supports `$target`, or `null` if none does. */
    public function resolve(RenderTarget $target): ?ComponentRendererInterface
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supportsTarget($target)) {
                return $renderer;
            }
        }

        return null;
    }

    /**
     * The renderer for `$componentName` at `$target`: the most recently {@see registerFor()}ed one
     * that supports the target, else `null`. A target-wide {@see register()}ed renderer is not
     * consulted — see the class docblock for why.
     */
    public function resolveFor(string $componentName, RenderTarget $target): ?ComponentRendererInterface
    {
        foreach ($this->byComponent[$componentName] ?? [] as $renderer) {
            if ($renderer->supportsTarget($target)) {
                return $renderer;
            }
        }

        return null;
    }
}
