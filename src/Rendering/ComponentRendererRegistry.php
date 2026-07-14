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
 */
final class ComponentRendererRegistry implements ComponentRendererRegistryInterface
{
    /**
     * @var array<int, ComponentRendererInterface>
     */
    private array $renderers = [];

    /** Registers `$renderer`, taking precedence over any already-registered renderer for the same target. */
    public function register(ComponentRendererInterface $renderer): void
    {
        array_unshift($this->renderers, $renderer);
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
}
