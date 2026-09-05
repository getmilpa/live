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

namespace Milpa\Live\Contracts\Component;

/**
 * A {@see ComponentRegistryInterface} that can enumerate what it holds.
 *
 * Kept as a sibling interface rather than a method on the registry contract
 * so existing implementations stay valid (additive, greenhouse
 * decisions/0211). Composition needs it: a
 * {@see \Milpa\Live\Runtime\CompositeComponentRegistry} can only detect that
 * two layers bind the same name to different definitions — and can only list
 * their union — for layers that implement this.
 */
interface ListsComponents
{
    /**
     * Every registered component name, in registration order.
     *
     * @return list<string>
     */
    public function names(): array;
}
