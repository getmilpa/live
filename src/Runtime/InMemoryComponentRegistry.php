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
 * The default in-memory {@see ComponentRegistryInterface}: a plain
 * `name => ComponentDefinitionInterface` map, with no persistence or
 * discovery — callers {@see register()} every component explicitly.
 */
final class InMemoryComponentRegistry implements ComponentRegistryInterface, ListsComponents
{
    /** @var array<string, ComponentDefinitionInterface> */
    private array $components = [];

    /** True when a component is registered under `$name`. */
    public function has(string $name): bool
    {
        return isset($this->components[$name]);
    }

    /**
     * Returns the component registered under `$name`.
     *
     * @throws \RuntimeException If no component is registered under `$name`.
     */
    public function get(string $name): ComponentDefinitionInterface
    {
        if (!$this->has($name)) {
            throw new \RuntimeException("Component not registered: {$name}");
        }

        return $this->components[$name];
    }

    /** Registers `$component` under `$name`, replacing any component already registered under it. */
    public function register(string $name, ComponentDefinitionInterface $component): void
    {
        $this->components[$name] = $component;
    }

    /**
     * Every registered name, in registration order (a replaced name keeps its original slot).
     *
     * @return list<string>
     */
    public function names(): array
    {
        // array_keys hands a numeric-string name back as an int; the contract is list<string>.
        return array_map(strval(...), array_keys($this->components));
    }
}
