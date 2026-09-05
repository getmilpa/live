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

use Milpa\Live\ValueObjects\ClientAssets;

/**
 * A renderer that needs the host page to load client-side files — the
 * component's Alpine data factory, its stylesheet — declares them here
 * (greenhouse decisions/0211).
 *
 * This is a SIBLING of {@see ComponentRendererInterface}, implemented by the
 * renderer of the target that needs the files (an HTML renderer), never by
 * a {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface}: the
 * component contract stays render-target-agnostic, and a TUI renderer of the
 * same component has nothing to declare. A compiler that meets a renderer
 * implementing this collects its {@see ClientAssets} into the compile result
 * ({@see \Milpa\Live\ValueObjects\RenderResult::clientAssets()}) so the host
 * emits every URL once.
 */
interface DeclaresClientAssets
{
    /**
     * The client files this renderer's output depends on — URLs the plugin
     * serves from its own routes. Stable for the renderer's lifetime; it MUST
     * NOT depend on which component instance is being rendered.
     */
    public function clientAssets(): ClientAssets;
}
