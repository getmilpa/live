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

namespace Milpa\Live\ValueObjects;

/**
 * The output of {@see \Milpa\Live\Contracts\Rendering\ComponentRendererInterface::render()}.
 * `$state` MUST be populated whenever the renderer mounted the component
 * itself (see {@see RenderRequest::$state}), so callers that need to
 * persist or re-encode the resulting state do not have to re-mount to get
 * it.
 */
final readonly class RenderResult
{
    /**
     * @param string                           $output  The rendered output — HTML markup, or a TUI text block, per `$format`.
     * @param StateSnapshot|null               $state   The state that was rendered, populated whenever the renderer mounted
     *                                                  or otherwise resolved one.
     * @param array<string, mixed>             $assets  Target-specific client assets/metadata (e.g. `'script'` URL, TUI key hints).
     * @param array<int, array<string, mixed>> $effects Side effects the caller should apply alongside the output.
     * @param RenderTarget                     $format  Which target this output was rendered for.
     */
    public function __construct(
        public string $output,
        public ?StateSnapshot $state = null,
        public array $assets = [],
        public array $effects = [],
        public RenderTarget $format = RenderTarget::HTML,
    ) {
    }
}
