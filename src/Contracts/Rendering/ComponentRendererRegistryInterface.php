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

namespace Milpa\Live\Contracts\Rendering;

use Milpa\Live\ValueObjects\RenderTarget;

/**
 * Resolves a {@see ComponentRendererInterface} by render target, mirroring
 * the {@see \Milpa\Live\Contracts\Tui\TuiNodeRendererRegistryInterface}
 * pattern (register + supports-based resolve) on the component-renderer
 * side of the render seam.
 */
interface ComponentRendererRegistryInterface
{
    /**
     * Registers a renderer. Implementations that keep an ordered list and
     * resolve by first match SHOULD prefer the most recently registered
     * renderer when more than one claims to
     * {@see ComponentRendererInterface::supportsTarget()} the same target.
     */
    public function register(ComponentRendererInterface $renderer): void;

    /**
     * Looks up the renderer that {@see ComponentRendererInterface::supportsTarget()}
     * the given target.
     *
     * @return ComponentRendererInterface|null `null` if no registered renderer supports this target — callers
     *                                         decide whether that is fatal.
     */
    public function resolve(RenderTarget $target): ?ComponentRendererInterface;
}
