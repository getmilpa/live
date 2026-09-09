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
 * What a component needs on the page besides its markup, declared by the component itself.
 *
 * A component used to be four pieces in four packages: its contract here, its template in the HTML
 * transport, its design contract in a npm package, and its stylesheet in whichever CONSUMER happened
 * to render it. The last one had no seam at all — a plugin could register a component but not dress
 * it, so its look had to be patched into somebody else's bundle. This is that seam.
 *
 * Paths are ABSOLUTE and the component builds them from its own location (`__DIR__`), because the
 * component is the only thing that knows where its package lives. They are read by the render target
 * that understands them — the HTML orchestrator reads `styles`; a TUI target would ignore it — which
 * is why this stays a declaration and never a loaded string: the contract is machine-readable and
 * travels into catalogues, and a stylesheet's bytes have no business there.
 */
final readonly class ComponentPresentation
{
    /**
     * @param string|null $styles Absolute path to the component's stylesheet.
     * @param string|null $script Absolute path to the component's client script.
     */
    public function __construct(
        public ?string $styles = null,
        public ?string $script = null,
    ) {
    }

    public function declaresAnything(): bool
    {
        return $this->styles !== null || $this->script !== null;
    }
}
