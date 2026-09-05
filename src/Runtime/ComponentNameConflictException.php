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

namespace Milpa\Live\Runtime;

/**
 * Two layers of a {@see CompositeComponentRegistry} bind the same component
 * name to DIFFERENT definitions — a different class, or a different instance
 * of a class that carries state. Thrown at construction (and on a conflicting
 * {@see CompositeComponentRegistry::register()}) so a host learns which two
 * plugins collide before the first render, never by one silently shadowing
 * the other (greenhouse decisions/0211; the analogue of the admin's
 * `SectionConflictException`).
 */
final class ComponentNameConflictException extends \LogicException
{
    public function __construct(
        public readonly string $component,
        public readonly string $firstLayer,
        public readonly string $secondLayer,
    ) {
        parent::__construct(sprintf(
            'Component "%s" is bound to different definitions in layers "%s" and "%s"; a composite registry never lets one shadow the other.',
            $component,
            $firstLayer,
            $secondLayer,
        ));
    }
}
