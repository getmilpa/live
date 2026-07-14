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
 * The ambient, render-target-agnostic context a component is
 * mounted/rendered within — its identity plus request-scoped facts
 * (principal, locale, route) it may need but does not own. Passed
 * alongside props to {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface::mount()}
 * so mount-time logic can depend on "who is asking" without every
 * component needing its own principal/locale plumbing.
 */
final readonly class ComponentContext
{
    /**
     * @param string               $componentId The component instance's unique id within the page/response.
     * @param string|null          $principal   The requesting {@see SecurityPrincipal}'s id, if authenticated.
     * @param string|null          $locale      The request's locale, if the caller resolved one.
     * @param string|null          $route       The current route/URL, for components that need it (e.g. active-nav state).
     * @param array<string, mixed> $meta        Caller-defined extra context beyond the fields above.
     */
    public function __construct(
        public string $componentId,
        public ?string $principal = null,
        public ?string $locale = null,
        public ?string $route = null,
        public array $meta = [],
    ) {
    }
}
