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

namespace Milpa\Live\Contracts\Data;

use Milpa\Live\ValueObjects\DataSourceRequest;
use Milpa\Live\ValueObjects\DataSourceResult;

/**
 * A named source of list data (e.g. for an autocomplete or data table) a
 * component can query at request time. Implementations are looked up by
 * name through a {@see DataSourceRegistryInterface} rather than injected
 * directly, so a component's markup/props can name a data source without
 * the component needing a compile-time dependency on it.
 */
interface DataSourceInterface
{
    /**
     * Whether this data source answers to the given `source` name. Used by
     * {@see DataSourceRegistryInterface::resolve()} to dispatch by name
     * instead of every call site hand-wiring a specific source.
     */
    public function supports(string $source): bool;

    /**
     * Resolves the request (query/filters/pagination) against this data
     * source's underlying data and returns the matching items.
     */
    public function resolve(DataSourceRequest $request): DataSourceResult;
}
