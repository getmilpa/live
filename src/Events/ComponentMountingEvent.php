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

namespace Milpa\Live\Events;

use Milpa\Live\ValueObjects\ComponentContext;

/**
 * PRE event: a component is about to be mounted via
 * {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface::mount()}.
 *
 * Dispatched as `component.mounting` — pure notification, readonly, no
 * {@see \Milpa\Events\InterceptionSlot}. Mount has no veto/short-circuit
 * seam in this catalog (unlike {@see ComponentHandlingEvent}/
 * {@see ComponentRenderingEvent}): a component's initial state is not a
 * point a plugin reasonably intercepts, only one it might want to audit
 * (e.g. mount-time metrics, tracing).
 */
final readonly class ComponentMountingEvent
{
    /**
     * @param string               $componentName The component contract's registered name.
     * @param array<string, mixed> $props         Mount-time props, exactly as passed to `mount()`.
     * @param ComponentContext     $context       The ambient context `mount()` was called with.
     */
    public function __construct(
        public string $componentName,
        public array $props,
        public ComponentContext $context,
    ) {
    }
}
