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

namespace Milpa\Live\DataSource;

use Milpa\Live\Contracts\Data\DataSourceInterface;
use Milpa\Live\Contracts\Data\DataSourceRegistryInterface;

/**
 * The default in-memory {@see DataSourceRegistryInterface}: resolves a
 * source name by asking each {@see register()}ed {@see DataSourceInterface}
 * (most recently registered first) whether it {@see DataSourceInterface::supports()}
 * it.
 */
final class InMemoryDataSourceRegistry implements DataSourceRegistryInterface
{
    /** @var list<DataSourceInterface> */
    private array $sources = [];

    /** Registers `$source`, taking precedence over any already-registered source. */
    public function register(DataSourceInterface $source): void
    {
        array_unshift($this->sources, $source);
    }

    /**
     * Returns the first registered source that {@see DataSourceInterface::supports()} `$source`.
     *
     * @throws \RuntimeException If no registered source supports `$source`.
     */
    public function resolve(string $source): DataSourceInterface
    {
        foreach ($this->sources as $candidate) {
            if ($candidate->supports($source)) {
                return $candidate;
            }
        }

        throw new \RuntimeException("Data source not registered: {$source}");
    }
}
