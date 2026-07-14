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
 * A client-originated action against a mounted component, as dispatched
 * to {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface::handle()}.
 * `$state` is the component's state as the caller currently trusts it —
 * for the HTTP live loop specifically, that trust comes from the state
 * envelope's signature having already been verified before this request
 * is constructed, not from anything in this value object itself.
 */
final readonly class InteractionRequest
{
    /**
     * @param string               $componentId   The component instance's id; MUST match `$state->componentId`.
     * @param string               $componentName The component's registered name; MUST match `$state->componentName`.
     * @param string               $action        The action name, as declared in the component's {@see ComponentContract::$actions}.
     * @param StateSnapshot        $state         The component's state immediately before this action is applied.
     * @param array<string, mixed> $payload       Action-specific input data (e.g. a form field's new value).
     * @param array<string, mixed> $meta          Caller-defined extra context beyond the fields above.
     */
    public function __construct(
        public string $componentId,
        public string $componentName,
        public string $action,
        public StateSnapshot $state,
        public array $payload = [],
        public array $meta = [],
    ) {
    }
}
