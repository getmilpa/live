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

namespace Milpa\Live\Contracts\Data;

/**
 * Resolves a {@see DataSourceInterface} by name via a register + supports-
 * based lookup — the same pattern
 * {@see \Milpa\Live\Contracts\Rendering\ComponentRendererRegistryInterface}
 * and {@see \Milpa\Live\Contracts\Tui\TuiNodeRendererRegistryInterface}
 * use for their own targets.
 */
interface DataSourceRegistryInterface
{
    /**
     * Registers a data source. Implementations that keep an ordered list
     * and resolve by first match SHOULD prefer the most recently
     * registered source when more than one claims to
     * {@see DataSourceInterface::supports()} the same name.
     */
    public function register(DataSourceInterface $source): void;

    /**
     * Looks up the data source that {@see DataSourceInterface::supports()}
     * the given name.
     *
     * @throws \RuntimeException If no registered source supports `$source`.
     */
    public function resolve(string $source): DataSourceInterface;
}
