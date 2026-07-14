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
 * The render targets a {@see \Milpa\Live\Contracts\Rendering\ComponentRendererInterface}
 * may declare support for. JSON and PLAIN were removed because nothing in
 * the lab implements them. ANSI is a declared exception to "proven": it
 * is kept as a forward-looking target for TUI-adjacent renderers, but no
 * renderer in the lab implements it yet — `supportsTarget(ANSI)` is
 * `false` everywhere today. Re-add JSON/PLAIN, or wire a real ANSI
 * renderer, only once something in the lab actually needs one.
 */
enum RenderTarget: string
{
    case HTML = 'html';
    case TUI = 'tui';
    case ANSI = 'ansi';
}
