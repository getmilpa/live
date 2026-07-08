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

namespace Milpa\Live\ValueObjects;

/**
 * A component instance's full state at one point in time — the value
 * every render-target and transport in this package passes around
 * instead of a live component object, since components themselves are
 * stateless between calls (see
 * {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface}).
 * `$data` and `$meta` are conventionally distinguished as: `$data` is the
 * component's primary, user-mutable value(s); `$meta` is configuration/
 * display state set at mount time (labels, flags, options) that
 * {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface::handle()}
 * does not normally rewrite. Nothing in this class enforces that split —
 * it is a convention components and renderers follow.
 */
final readonly class StateSnapshot
{
    /**
     * @param string               $componentId   The component instance's unique id.
     * @param string               $componentName The component's registered name (its {@see ComponentContract::$name}).
     * @param string               $version       The contract version this snapshot was mounted under (its
     *                                            {@see ComponentContract::$contractVersion}).
     * @param array<string, mixed> $data          Primary, user-mutable state.
     * @param array<string, mixed> $meta          Mount-time configuration/display state.
     */
    public function __construct(
        public string $componentId,
        public string $componentName,
        public string $version,
        public array $data = [],
        public array $meta = [],
    ) {
    }
}
