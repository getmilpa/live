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

namespace Milpa\Live\Events;

use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\StateSnapshot;

/**
 * POST event: a component finished mounting. Dispatched as
 * `component.mounted` — pure notification, readonly, no slot; only fires
 * once {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface::mount()}
 * has returned a {@see StateSnapshot} successfully. An exception thrown
 * during mount (e.g. autocomplete's "requires a non-empty source" guard)
 * means this event never fires for that call — {@see ComponentMountingEvent}
 * still fired first, unconditionally.
 */
final readonly class ComponentMountedEvent
{
    /**
     * @param string               $componentName The component contract's registered name.
     * @param array<string, mixed> $props         The mount-time props that were used.
     * @param ComponentContext     $context       The ambient context `mount()` was called with.
     * @param StateSnapshot        $state         The freshly-mounted state.
     */
    public function __construct(
        public string $componentName,
        public array $props,
        public ComponentContext $context,
        public StateSnapshot $state,
    ) {
    }
}
