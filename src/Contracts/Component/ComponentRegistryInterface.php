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

namespace Milpa\Live\Contracts\Component;

/**
 * A name-keyed lookup of {@see ComponentDefinitionInterface} instances.
 * Every render path (HTML compiler, TUI component node, HTTP live
 * endpoint) resolves components exclusively through here — by contract
 * name, never by class reference — so a component's implementation can be
 * swapped without touching call sites.
 */
interface ComponentRegistryInterface
{
    /**
     * Whether a component is registered under this name.
     */
    public function has(string $name): bool;

    /**
     * Looks up a registered component by name.
     *
     * @throws \RuntimeException If no component is registered under `$name`. Callers MUST check
     *                           {@see has()} first when a missing component is an expected,
     *                           recoverable case rather than a programming error.
     */
    public function get(string $name): ComponentDefinitionInterface;

    /**
     * Registers a component under the given name, replacing any existing
     * registration for that name.
     */
    public function register(string $name, ComponentDefinitionInterface $component): void;
}
