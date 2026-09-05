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

use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Contracts\Component\ComponentRegistryInterface;
use Milpa\Live\Contracts\Component\ListsComponents;

/**
 * One {@see ComponentRegistryInterface} over ordered, LABELLED layers — the
 * host's own components, then each plugin's — so ONE live endpoint serves
 * every plugin's components and a cross-component effect resolves across
 * them (greenhouse decisions/0211).
 *
 * Resolution is first-layer-wins, but shadowing is never silent: at
 * construction every name that appears in two layers is checked, and a name
 * bound to DIFFERENT definitions (a different class, or a different instance
 * of a class that carries state) throws {@see ComponentNameConflictException}
 * naming the component and both layers. The same instance registered in two
 * layers is fine, and so are two instances of a STATELESS class (no instance
 * property at all) — with nothing to differ in, they are one definition. The
 * check is identity or statelessness, never a structural compare: two
 * instances of a stateful class are a conflict even when their state would
 * compare equal (a deep `==` over a component that holds a collaborator
 * pointing back at it is a fatal, not a verdict). The shipped components hold
 * a dispatcher, so two `new TextareaComponent()` under one name in two layers
 * ARE a conflict: a plugin reuses the host's instance or names its own
 * component. Conflict detection and
 * {@see names()} cover the layers that implement {@see ListsComponents}; an
 * opaque layer still resolves but cannot be enumerated, so it takes no part
 * in either.
 *
 * Writes go to the one layer named writable (a plugin registering late, a
 * host adopting a section); with no writable layer the composite is
 * read-only and {@see register()} throws.
 */
final class CompositeComponentRegistry implements ComponentRegistryInterface, ListsComponents
{
    /** @var array<string, ComponentRegistryInterface> label => layer, in resolution order */
    private array $layers;

    /**
     * @param array<string, ComponentRegistryInterface> $layers   label => registry, in resolution order (first wins)
     * @param string|null                               $writable the label of the layer {@see register()} writes to, or null for a read-only composite
     *
     * @throws \InvalidArgumentException      if there are no layers, or `$writable` names no layer
     * @throws ComponentNameConflictException if two layers bind one name to different definitions
     */
    public function __construct(array $layers, private readonly ?string $writable = null)
    {
        if ($layers === []) {
            throw new \InvalidArgumentException('A composite component registry needs at least one layer.');
        }
        if ($writable !== null && !isset($layers[$writable])) {
            throw new \InvalidArgumentException(sprintf('No layer labelled "%s" to write to; layers: %s.', $writable, implode(', ', array_keys($layers))));
        }
        $this->layers = $layers;
        $this->assertNoConflicts();
    }

    /** True when any layer has a component under `$name`. */
    public function has(string $name): bool
    {
        return $this->layerFor($name) !== null;
    }

    /**
     * The component under `$name` from the first layer that has it.
     *
     * @throws \RuntimeException If no layer has a component under `$name`.
     */
    public function get(string $name): ComponentDefinitionInterface
    {
        $layer = $this->layerFor($name);
        if ($layer === null) {
            throw new \RuntimeException("Component not registered: {$name}");
        }

        return $layer->get($name);
    }

    /**
     * Registers `$component` in the writable layer.
     *
     * @throws \LogicException                if the composite has no writable layer
     * @throws ComponentNameConflictException if another layer already binds `$name` to a different definition
     */
    public function register(string $name, ComponentDefinitionInterface $component): void
    {
        if ($this->writable === null) {
            throw new \LogicException(sprintf('This composite component registry is read-only: no writable layer to register "%s" into.', $name));
        }
        foreach ($this->layers as $label => $layer) {
            if ($label !== $this->writable && $layer->has($name) && !self::sameDefinition($layer->get($name), $component)) {
                throw new ComponentNameConflictException($name, (string) $label, $this->writable);
            }
        }
        $this->layers[$this->writable]->register($name, $component);
    }

    /**
     * The union of every listing layer's names, in layer order then
     * registration order, each name once.
     *
     * @return list<string>
     */
    public function names(): array
    {
        $names = [];
        foreach ($this->layers as $layer) {
            if (!$layer instanceof ListsComponents) {
                continue;
            }
            foreach ($layer->names() as $name) {
                $names[$name] = true;
            }
        }

        return array_map(strval(...), array_keys($names));
    }

    /**
     * The layer labels, in resolution order.
     *
     * @return list<string>
     */
    public function labels(): array
    {
        return array_map(strval(...), array_keys($this->layers));
    }

    private function layerFor(string $name): ?ComponentRegistryInterface
    {
        foreach ($this->layers as $layer) {
            if ($layer->has($name)) {
                return $layer;
            }
        }

        return null;
    }

    private function assertNoConflicts(): void
    {
        /** @var array<string, array{string, ComponentDefinitionInterface}> $seen name => [label, definition] */
        $seen = [];
        foreach ($this->layers as $label => $layer) {
            if (!$layer instanceof ListsComponents) {
                continue;
            }
            foreach ($layer->names() as $name) {
                $definition = $layer->get($name);
                if (!isset($seen[$name])) {
                    $seen[$name] = [(string) $label, $definition];

                    continue;
                }
                [$firstLabel, $first] = $seen[$name];
                if (!self::sameDefinition($first, $definition)) {
                    throw new ComponentNameConflictException($name, $firstLabel, (string) $label);
                }
            }
        }
    }

    /**
     * The same instance, or two instances of one STATELESS class — anything
     * else is a different definition. Deliberately not `==`: a structural
     * compare recurses into whatever the component holds, and a collaborator
     * that points back at the component (a dispatcher listing it as a
     * listener, a container-held service) turns the check into an uncatchable
     * "nesting level too deep" fatal instead of a named exception.
     */
    private static function sameDefinition(ComponentDefinitionInterface $a, ComponentDefinitionInterface $b): bool
    {
        if ($a === $b) {
            return true;
        }

        return $a::class === $b::class && self::isStateless($a);
    }

    /**
     * True when the instance carries no state of its own: no instance property
     * — declared anywhere in its class chain, or dynamic — only statics at most.
     */
    private static function isStateless(object $component): bool
    {
        for ($class = new \ReflectionObject($component); $class !== false; $class = $class->getParentClass()) {
            foreach ($class->getProperties() as $property) {
                if (!$property->isStatic()) {
                    return false;
                }
            }
        }

        return true;
    }
}
