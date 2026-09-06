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
 * The contract by which a host declares the components it brings.
 *
 * Discovery is by `instanceof` over the booted plugins, at request time — boot order does not
 * matter and nobody names a plugin, exactly as the admin panel discovers its sections. The
 * framework's own primitives enter through this same interface
 * ({@see \Milpa\Live\Components\Library}): there is no privileged path.
 *
 * It declares CLASSES, not instances, and that is the whole reason it can answer cheaply:
 * {@see ComponentDefinitionInterface::contract()} is static, so a catalogue can read what a
 * component IS without building it, without a registry, and without a request. Registration
 * answers a different question — what this host will PAINT right now — and a name can be
 * registered by one host and declared by another.
 *
 * Kept as a sibling interface rather than a method on {@see ComponentRegistryInterface} so
 * existing implementations stay valid (additive, the shape {@see ListsComponents} already set
 * for greenhouse decisions/0211).
 */
interface DeclaresComponents
{
    /**
     * The component definitions this host declares, as class-strings.
     *
     * @return list<class-string<ComponentDefinitionInterface>>
     */
    public function declaredComponents(): array;
}
